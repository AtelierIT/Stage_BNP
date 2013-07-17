<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Directpay_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::getRequestFile()
     *
     * @return string
     */
    public function getRequestFile()
    {
        return 'sofort.aspx';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_preProcessRequestData()
     */
    protected function _preProcessRequestData()
    {
        parent::_preProcessRequestData();

        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();

        //Unset the key for direct pay
        $encryptData->unsetData('otfMethod');
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        parent::_getRequestData();

        //Get the encrypted request object
        $encryptData    = $this->_getEncryptionDataObject();

        $orderDesc2      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc2')
        );

        $encryptData['OrderDesc2']  = $orderDesc2;

        //Unset the data
        $encryptData->unsetData('AutoStart');

        //Override the information
        $encryptData['URLSuccess']  = Mage::getUrl('paymentoperator/callback_directpay/success', array('_forced_secure' => true));
        $encryptData['URLFailure']  = Mage::getUrl('paymentoperator/callback_directpay/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']   = Mage::getUrl('paymentoperator/callback_directpay/notify', array('_forced_secure' => true));
    }
}