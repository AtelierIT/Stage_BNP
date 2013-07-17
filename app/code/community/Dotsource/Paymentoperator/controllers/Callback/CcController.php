<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Callback_CcController
    extends Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
{

    /** Enable javascript redirect for breaking out the iframe */
    protected $_javaScriptRedirect = true;


    /**
     * @see Dotsource_Paymentoperator_Controller_Paymentoperatorcallback::_getPaymentCode()
     *
     * @return string
     */
    protected function _getPaymentCode()
    {
        return 'paymentoperator_cc';
    }

    /**
     * We need an additional implementation of the error handling to fix a 3d secure problem. Its possible that
     * the customer payed successfully with 3d secure and the shop gets notified. After that the shop gets also
     * a timeout from the payment operator. In this case we need to ignore the false timeout error.
     *
     * @param   Dotsource_Paymentoperator_Model_Payment_Response_Response   $response
     */
    protected function _notifyErrorProcessing(Dotsource_Paymentoperator_Model_Payment_Response_Response $response)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order      = Mage::registry('paymentoperator_notify_order');
        $payment    = $order->getPayment();

        //Its possible that a transaction timeout will triggered after the order was successfully marked as paid
        //this is a 3d secure problem/bug
        if ($payment->getLastTransId()) {
            //If we have a last transaction id this means a valid auth/capture was processed and if the current
            //error code is a timeout error so we can skip the error logic
            $order->addStatusHistoryComment(
                $this->createMessage(
                    $this->_getHelper()->__(
                        'A payment error was send after a successful payment notification. The payment error will be ignored.'
                    )
                )
            );

            return;
        }

        parent::_notifyErrorProcessing($response);
    }

    /**
     * @see Dotsource_Paymentoperator_Controller_Paymentoperatorcallback::_notifySuccessProcessing()
     *
     * @param Dotsource_Paymentoperator_Model_Payment_Response_Response $response
     */
    protected function _notifySuccessProcessing(Dotsource_Paymentoperator_Model_Payment_Response_Response $response)
    {
        //Do the parent stuff first
        parent::_notifySuccessProcessing($response);

        /* @var $order Mage_Sales_Model_Order */
        $order      = Mage::registry('paymentoperator_notify_order');
        $payment    = $order->getPayment();

        //Check for storing pseudo data
        if (!$payment->getMethodInstance()->getConfigData('use_pseudo_data', $order->getStoreId())) {
            return;
        }

        //Try to store pseudo cc data
        if (!$response->getResponse()->emptyPcnr()) {
            $payment
                ->setCcNumberEnc(
                    Mage::helper('core')->getEncryptor()->encrypt(
                        $response->getResponse()->getPcnr()
                    )
                )
                ->setCcLast4(substr($response->getResponse()->getPcnr(), -3))
                ->setCcExpYear(substr($response->getResponse()->getCcexpiry(), 0, 4))
                ->setCcExpMonth(substr($response->getResponse()->getCcexpiry(), -2))
                ->setCcType($response->getResponse()->getCcbrand());
        }
    }
}