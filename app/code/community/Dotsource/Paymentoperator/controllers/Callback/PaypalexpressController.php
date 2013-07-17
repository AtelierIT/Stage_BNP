<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.05.2010 16:48:28
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Callback_PaypalexpressController
    extends Dotsource_Paymentoperator_Controller_Expresscallback
{

    /**
     * Retreives the code for the paymentmethod.
     * @see Dotsource_Paymentoperator_Controller_Paymentoperatorcallback::_getPaymentCode()
     *
     * @return  string
     */
    protected function _getPaymentCode()
    {
        return 'paymentoperator_paypal_express';
    }


    /**
     * Startaction for the paypal express checkout.
     *
     * @return  null
     */
    public function startAction()
    {
        try {
            $quote = $this->_getHelper()->getFrontendQuote();

            if ($quote && !$quote->hasItems()) {
                $this->getResponse()->setHeader('HTTP/1.1', '403 Forbidden');
                throw new Dotsource_Paymentoperator_Model_Exception(
                    Mage::helper('paymentoperator')->__('Unable to initialize Express Checkout')
                );
            }

            //Set the payment in quote the quote will automatically saved
            Mage::getSingleton('checkout/type_onepage')->savePayment(
                array('method' => $this->_getPaymentCode())
            );

            //Reload the quote to get the right save handling
            $quote = Mage::getModel('sales/quote')->load($quote->getId());

            //Reserve an order increment id
            $quote->reserveOrderId();

            $url = $quote->getPayment()->getMethodInstance()->getRequestUrl(
                $quote->getPayment(),
                $quote->getBaseGrandTotal()
            );

            //Save payment
            $quote->getPayment()->save();

            //Save quote to persist the paymentoperator_transaction_id
            $quote->save();

            $this->getResponse()->setRedirect($url);
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->getCheckoutSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getHelper()->getCheckoutSession()->addError($this->__('Unable to start Express Checkout.'));
        }

        $this->_redirect('checkout/cart');
    }
}