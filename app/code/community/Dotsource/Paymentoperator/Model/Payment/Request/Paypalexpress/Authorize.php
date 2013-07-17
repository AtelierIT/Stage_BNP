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

class Dotsource_Paymentoperator_Model_Payment_Request_Paypalexpress_Authorize
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

        //Set the payment action
        switch ($paymentAction) {
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                $encryptData['Capture'] = 'MANUAL';
                $encryptData['Txtype']  = 'Order';
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING);
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                $encryptData['Capture'] = 'MANUAL';
                $encryptData['Txtype'] = $this->getPaymentMethod()->getConfigData('payment_action_txtype');
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE);
                break;
            default:
                Mage::throwException('Can\'t detect the payment moment.');
        }

        //This information is needed to check if the right controller is called
        $userData->setPaymentCode($this->getPaymentMethod()->getCode());
    }


    /**
     * Retrieves the default country model.
     *
     * @return  Mage_Directory_Model_Country
     */
    protected function _getDefaultCountryModel()
    {
        $defaultCountryId = Mage::getStoreConfig('general/country/default');
        $model = Mage::getModel('directory/country');
        return $model->load($defaultCountryId);
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();
        /* @var $conv Dotsource_Paymentoperator_Helper_Converter */
        $conv           = $this->_getConverter();

        //Get the data we need
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        //Get the quote to a converted order desc
        $convertedQuoteOrderDesc = $conv->convertOrderToInformationOrderDesc($this->getPayment()->getQuote());

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

        //Callback urls for paymentoperator
        $encryptData['URLSuccess'] = Mage::getUrl('paymentoperator/callback_paypalexpress/success', array('_forced_secure' => true));
        $encryptData['URLFailure'] = Mage::getUrl('paymentoperator/callback_paypalexpress/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']  = Mage::getUrl('paymentoperator/callback_paypalexpress/notify', array('_forced_secure' => true));
    }
}