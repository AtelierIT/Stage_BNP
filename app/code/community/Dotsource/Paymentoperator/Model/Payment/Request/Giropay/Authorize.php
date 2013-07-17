<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'payOTF.aspx';
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

        //Authorize is always a async payment
        $this
            ->getPayment()
            ->setIsTransactionPending(true)
            ->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE);

        //Set the oft method
        $otfMethod = $this->getPaymentMethod()->getConfigData('oftmethod');
        if ($otfMethod) {
            $encryptData['otfMethod'] = $this->getPaymentMethod()->getConfigData('oftmethod');
        }

        //Add the mode to the user data and the payment code
        //The information are needed to check if the right controller is called
        $userData
            ->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING)
            ->setPaymentCode($this->getPaymentMethod()->getCode());
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();
        $infoInstance   = $this->getPaymentMethod()->getInfoInstance();
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        //Set the encrypt data
        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['OrderDesc']       = $encryptData['TransID'] . " " .$orderDesc;
        $encryptData['Amount']          = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']        = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        $encryptData['Response']        = "encrypt";

        //Callback urls for paymentoperator
        $encryptData['URLSuccess']      = Mage::getUrl('paymentoperator/callback_giropay/success', array('_forced_secure' => true));
        $encryptData['URLFailure']      = Mage::getUrl('paymentoperator/callback_giropay/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']       = Mage::getUrl('paymentoperator/callback_giropay/notify', array('_forced_secure' => true));

        //Set the data if they exists in the info instance
        if ($infoInstance->hasEftOwner()
            && $infoInstance->hasEftBanEnc()
            && $infoInstance->hasEftBcn()
        ) {
            //Owner
            $encryptData['AccOwner']    = $infoInstance->getEftOwner();

            //Bank account number
            $encryptData['AccNr']       = $infoInstance->decrypt($infoInstance->getEftBanEnc());
            $this->_addDangerousTag('AccNr');

            //Bank code number
            $encryptData['AccIBAN']     = $infoInstance->getEftBcn();

            //No need the change payment information on giropay site
            $encryptData['AutoStart']   = '1';
        }
    }
}