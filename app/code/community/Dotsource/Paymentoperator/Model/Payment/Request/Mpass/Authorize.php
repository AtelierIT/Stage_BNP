<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 30.04.2010 17:10:23
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Model_Payment_Request_Mpass_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'mpass.aspx';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_preProcessRequestData()
     */
    protected function _preProcessRequestData()
    {
        //Do the parent stuff first
        Dotsource_Paymentoperator_Model_Payment_Request_Request::_preProcessRequestData();

        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();
        $userData       = $this->_getUserDataObject();
        $paymentAction  = $this->getPaymentMethod()->getConfigData('payment_action_paymentoperator');

        //Authorize is always a async payment
        $this->getPayment()
            ->setIsTransactionPending(true);

        //Set the payment action
        switch ($paymentAction) {
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                $encryptData['Capture'] = 'AUTO';
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING);
                $this->getPayment()->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE);
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                $encryptData['Capture'] = 'MANUAL';
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE);
                $this->getPayment()->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_AUTHORIZATION);
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING:
                $encryptData['Capture'] = $this->getPaymentMethod()->getConfigData('showdebitinhours');
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING);
                $userData->setHours($this->getPaymentMethod()->getConfigData('showdebitinhours'));
                $this->getPayment()->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE);
                break;
            default:
                Mage::throwException('Can\'t detect the payment moment.');
        }

        //This information is needed to check if the right controller is called
        $userData->setPaymentCode($this->getPaymentMethod()->getCode());
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request object
        $encryptData    = $this->_getEncryptionDataObject();
        $order          = $this->getPayment()->getOrder();
        $billingAddress = $order->getBillingAddress();
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        //Set the encrypt data
        $encryptData['TransID']   = $this->_getIncrementId();
        $encryptData['RefNr']     = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['ReqID']     = $encryptData['TransID'].time();

        $encryptData['OrderDesc'] = $encryptData['TransID'] . " " .$orderDesc;
        $encryptData['Amount']    = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']  = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        $encryptData['Language']  = $this->_getHelper()->getPaymentHelper()->getPaymentgateLanguage(
            $this->getPaymentMethod()
        );

        //Callback urls for paymentoperator
        $encryptData['URLSuccess'] = Mage::getUrl('paymentoperator/callback_mpass/success', array('_forced_secure' => true));
        $encryptData['URLFailure'] = Mage::getUrl('paymentoperator/callback_mpass/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']  = Mage::getUrl('paymentoperator/callback_mpass/notify', array('_forced_secure' => true));
    }
}