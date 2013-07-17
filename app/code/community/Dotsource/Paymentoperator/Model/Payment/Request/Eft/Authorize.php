<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Eft_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /** Enable using ip zones */
    protected $_useIpZones      = true;

    /** Disable using country zones */
    protected $_useCountryZones = false;

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'edddirect.aspx';
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

        //Set the payment action
        switch ($paymentAction) {
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                $encryptData['Capture'] = "AUTO";
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING);
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                $encryptData['Capture'] = "MANUAL";
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE);
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING:
                $encryptData['Capture'] = $this->getPaymentMethod()->getConfigData('showdebitinhours');
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING);
                $userData->setHours($this->getPaymentMethod()->getConfigData('showdebitinhours'));
                break;
            default:
                Mage::throwException("Can't detect the payment moment.");
        }
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();

        //Get the data we need
        $infoInstance   = $this->getPaymentMethod()->getInfoInstance();

        //Get the order description
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        $orderDesc2      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc2')
        );

        //Set the encrypt data
        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['OrderDesc']       = $encryptData['TransID'] . " " .$orderDesc;
        $encryptData['OrderDesc2']      = $orderDesc2;
        $encryptData['Amount']          = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']        = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        $encryptData['Response']        = "encrypt";

        //Set the user account information
        $encryptData['AccOwner']        = $infoInstance->getEftOwner();
        $encryptData['AccNr']           = $infoInstance->decrypt($infoInstance->getEftBanEnc());
        $this->_addDangerousTag('AccNr');

        $encryptData['AccIBAN']         = $infoInstance->getEftBcn();
    }
}