<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Cc_Paymentgate_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /** Enable using ip zones */
    protected $_useIpZones      = true;

    /** enable using country zones */
    protected $_useCountryZones = true;


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'payssl.aspx';
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
        $this
            ->getPayment()
            ->setIsTransactionPending(true);

        //Set the payment action
        switch ($paymentAction) {
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                $encryptData['Capture'] = "AUTO";
                $userData->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING);
                $this->getPayment()->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE);
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                $encryptData['Capture'] = "MANUAL";
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
                Mage::throwException("Can't detect the payment moment.");
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
        $request        = $this->_getRequestObject();
        $encryptData    = $this->_getEncryptionDataObject();
        $infoInstance   = $this->getPaymentMethod()->getInfoInstance();
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        //Set the encrypt data
        $encryptData['TransID']      = $this->_getIncrementId();
        $encryptData['RefNr']        = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['ReqID']        = $encryptData['TransID'].time();

        $encryptData['OrderDesc']    = $encryptData['TransID'] . " " . $orderDesc;
        $encryptData['Amount']       = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']     = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        $encryptData['Response']     = 'encrypt';


        //Build the params for the user callback urls
        $sessionId = Mage::getSingleton('core/session')->getSessionId();
        $userUrlParams = array(Dotsource_Paymentoperator_Controller_Callback::SESSION_KEY_PARAM => $sessionId);

        //global param for all urls
        $globalParams = array('_forced_secure' => true);

        //Callback urls for paymentoperator
        $encryptData['URLSuccess']   = Mage::getUrl('paymentoperator/callback_cc/success', array_merge($globalParams, $userUrlParams));
        $encryptData['URLFailure']   = Mage::getUrl('paymentoperator/callback_cc/failure', array_merge($globalParams, $userUrlParams));
        $encryptData['URLNotify']    = Mage::getUrl('paymentoperator/callback_cc/notify', $globalParams);

        //Send this data not encrypted
        //check for using xslt
        if ($this->getPaymentMethod()->getConfigData('use_template')) {
            $request['Template']    = $this->getPaymentMethod()->getConfigData('template');
        }

        $request['Language']        = $this->_getHelper()->getPaymentHelper()->getPaymentgateLanguage(
            $this->getPaymentMethod()
        );

        $request['URLBack']         = Mage::getUrl('paymentoperator/callback_cc/back', array('_forced_secure' => true));

        //If we have all data from the info instance
        if ($this->getPaymentMethod()->isPrefillinformationActive()
            && $infoInstance->getCcPrefillInformation()
            && $infoInstance->getCcNumberEnc()
            && $infoInstance->getCcExpYear()
            && $infoInstance->getCcExpMonth()
            && $infoInstance->getCcType()
        ) {
            $request['PCNr']        = $infoInstance->decrypt($infoInstance->getCcNumberEnc());
            $this->_addDangerousTag('PCNr');

            $request['PCNrBrand']   = $infoInstance->getCcType();
            $request['PCNrMonth']   = $infoInstance->getCcExpMonth();
            $request['PCNrYear']    = $infoInstance->getCcExpYear();
        }
    }
}