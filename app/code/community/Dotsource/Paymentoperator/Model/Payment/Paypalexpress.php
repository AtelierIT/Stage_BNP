<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 27.04.2010 12:20:34
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Model_Payment_Paypalexpress
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{

    protected $_canUseCheckout      = false;

    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_paypalexpress';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paypal/payment_info';

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_paypal_express';

    /** Holds the path to the request models */
    protected $_requestModelInfo    = 'paymentoperator/payment_request_paypalexpress_';


    /**
     * Retrieve information from payment configuration.
     *
     * @return  Mage_Paypal_Model_Config
     */
    public function getConfig()
    {
        return Mage::getModel('paypal/config');
    }


    /**
     * Checkout redirect URL getter for onepage checkout.
     * @see Mage_Checkout_OnepageController::savePaymentAction()
     * @see Mage_Sales_Model_Quote_Payment::getCheckoutRedirectUrl()
     *
     * @return  string
     */
    public function getCheckoutRedirectUrl()
    {
        return Mage::getUrl('paymentoperator/callback_paypalexpress/start');
    }


    /**
     * Overwritten authorize method. This method uses the payment
     * additional information to rebuild a response that was recieved
     * in the callbackControllers notifyAction().
     *
     * @see Dotsource_Paymentoperator_CallbPaymentoperatoralexpressController::notifyAction().
     * @see Mage_Payment_Model_Method_Abstract::authorize()
     *
     * @param   Varien_Object   $payment
     * @param   float           $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        //Need quote payment for additional payment information
       $quotePayment = $this->_getHelper()->getFrontendQuote()->getPayment();

        //Fake a response with the additional information to create the magento
        //transaction information
        $responseModel = Mage::getModel('paymentoperator/payment_response_response');

        /* @var $responseModel Dotsource_Paymentoperator_Model_Payment_Response_Response */
        $responseModel->setResponse(
            array(
                'xid'   => $quotePayment->getAdditionalInformation('paymentoperator_xid'),
                'payid' => $quotePayment->getAdditionalInformation('paymentoperator_payid')
            ),
            false
        );

        if ($quotePayment->getAdditionalInformation('paymentoperator_is_pending')) {
            $payment
                ->setIsTransactionPending(true)
                ->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_AUTHORIZATION);
        } else {
            //Used for getConfigData('order_status')
            $this->setNewOrderStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::READY_FOR_CAPTURE);
        }

        //Don't close the capture transaction we are available to refund
        $payment->setIsTransactionClosed(false);

        //Add the transaction info to the payment
        $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment($payment, $responseModel);

        return $this;
    }


    /**
     * Check if we can capture direct.
     *
     * @return boolean
     */
    public function canBackendDirectCapture()
    {
        $quote = $this->_getHelper()->getFrontendQuote();
        return $quote->getId()
            && $quote->getPayment()->hasAdditionalInformation('paymentoperator_xid')
            && $quote->getPayment()->hasAdditionalInformation('paymentoperator_payid');
    }


    /**
     * This method is not used for backend direct capture. The method has refactored
     * for capturing in frontend by pressing the place oder button.
     *
     * @param Varien_Object $payment
     * @param float $amount
     */
    protected function _directBackendCapture(Varien_Object $payment, $amount)
    {
        //At this point we don't have a valid previous transaction for capture
        //But all the information are available in the current quote
        $quotePayment = $this->_getHelper()->getFrontendQuote()->getPayment();

        //Data we need from the quote
        $xid   = $quotePayment->getAdditionalInformation('paymentoperator_xid');
        $payid = $quotePayment->getAdditionalInformation('paymentoperator_payid');

        //Fake previous authorization transaction for capturing.
        //The authorization transaction was processed be clicking the paypal express
        //button and redirect to the review page
        $authTransaction = Mage::getModel('sales/order_payment_transaction')
            ->setXid($xid)
            ->setAdditionalInformation('payid', $payid);

        //Get the request model
        $request = $this->_createRequestModel('capture', $payment);

        //Set the invoice amount
        $request
            ->setReferencedTransactionModel($authTransaction)
            ->setAmount($amount);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );

            //Don't close the capture transaction we are available to refund
            $payment->setIsTransactionClosed(false);

            if ($request->getResponseModel()->isPending()) {
                $payment->setIsTransactionPending(true);
                $payment->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE);
            }
        } else {
            //Process the error manager
            $this->_getHelper()->getPaymentHelper()
            	->getPaymentErrorManager('capture')
                ->processErrorCode($request, $this->getCode());
        }
    }


    /**
     * @see Mage_Payment_Model_Method_Abstract::authorize()
     *
     * @param   Varien_Object $payment
     * @param   float         $amount
     * @return  string
     */
    public function getRequestUrl(Varien_Object $payment, $amount)
    {
        //Get the request model
        $requestModel = $this->_createRequestModel('authorize', $payment);

        //Set the amount
        $requestModel->setAmount($amount);

        //Process the request data and create the redirect url for the payment gateway
        return $this->_getHelper()->getConfiguration()->getBaseUrl() .
            $requestModel->getRequestFile() .
            '?' .
            $requestModel->getRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request::REQUEST_AS_STRING);
    }


    /**
	 * Retrieves the configuration for the payment method.
	 *
     * @param   string  $field
     * @param   integer $storeId
     * @return  mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('payment_action' == $field) {
            return $this->getConfigData('payment_action_paymentoperator', $storeId);
        }

        return parent::getConfigData($field, $storeId);
    }
}