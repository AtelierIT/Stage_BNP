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

class Dotsource_Paymentoperator_Model_Payment_Request_Paypal_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     *
     * @return  string
     */
    public function getRequestFile()
    {
        return 'paypal.aspx';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_preProcessRequestData()
     *
     * @return  null
     */
    protected function _preProcessRequestData()
    {
        //Do the parent stuff first
        Dotsource_Paymentoperator_Model_Payment_Request_Request::_preProcessRequestData();

        //Get the request objects
        /* @var $encryptData Dotsource_Paymentoperator_Object */
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
                $encryptData['Txtype'] = $this->getPaymentMethod()->getConfigData('payment_action_txtype');
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE);
                $this->getPayment()->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_AUTHORIZATION);
                break;
            // TODO maybe this isn't supported
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
        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();
        /* @var $conv Dotsource_Paymentoperator_Helper_Converter */
        $conv = $this->_getConverter();

        //Get the data we need
        $order          = $this->getPayment()->getOrder();
        $billingAddress = $order->getBillingAddress();
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        //Get the quote to a converted order desc
        $convertedQuoteOrderDesc = $conv->convertOrderToInformationOrderDesc($order);

        //Set the encrypt data
        $encryptData['TransID']      = $this->_getIncrementId();
        $encryptData['RefNr']        = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['ReqID']        = $encryptData['TransID'].time();

        $encryptData['Amount']       = $conv->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']     = $conv->convertToUtf8($this->_getCurrencyCode());
        $encryptData['OrderDesc']    = $encryptData['TransID'] . " " .$orderDesc;

        //Add the additional order desc for paypal
        foreach ($convertedQuoteOrderDesc as $key => $paypalOrderDescPart) {
            $encryptData["OrderDesc{$key}"] = $paypalOrderDescPart;
        }

        $encryptData['Response']     = 'encrypt';
        $encryptData['Language']     = $this->_getHelper()->getPaymentHelper()->getPaymentgateLanguage(
            $this->getPaymentMethod()
        );

        $shippingAddress = $order->getShippingAddress();

        //Set the address data
        $encryptData['FirstName']           = $conv->convertToUtf8($order->getCustomerFirstname());
        $encryptData['LastName']            = $conv->convertToUtf8($order->getCustomerLastname());

        // TODO should be forced to maxlength ?
        $encryptData['AddrStreet']          = $conv->convertToUtf8($shippingAddress->getStreet1());
        $encryptData['AddrStreet2']         = $conv->convertToUtf8($shippingAddress->getStreet2());
        $encryptData['AddrCity']            = $conv->convertToUtf8($shippingAddress->getCity());
        $encryptData['AddrState']           = $conv->convertToUtf8($shippingAddress->getRegion());
        $encryptData['AddrZIP']             = $conv->convertToUtf8($shippingAddress->getPostcode());
        $encryptData['AddrCountryCode']     = $conv->convertToUtf8($shippingAddress->getCountryModel()->getIso3Code());
        $encryptData['Phone']               = $conv->convertToUtf8($shippingAddress->getTelephone());
        $encryptData['BillingAddrStreet2']  = $conv->convertToUtf8($billingAddress->getStreetFull());

        //Callback urls for paymentoperator
        $encryptData['URLSuccess'] = Mage::getUrl('paymentoperator/callback_paypal/success', array('_forced_secure' => true));
        $encryptData['URLFailure'] = Mage::getUrl('paymentoperator/callback_paypal/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']  = Mage::getUrl('paymentoperator/callback_paypal/notify', array('_forced_secure' => true));
    }
}