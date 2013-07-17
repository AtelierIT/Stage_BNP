<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Ideal_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::_preProcessRequestData()
     */
    protected function _preProcessRequestData()
    {
        parent::_preProcessRequestData();

        //Get the request objects
        $encryptData    = $this->_getEncryptionDataObject();

        //Override the old value
        $encryptData['otfMethod']  = 'iDEAL';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::_getRequestData()
     */
    protected function _getRequestData()
    {
        parent::_getRequestData();

        //Get the encrypted request object
        $encryptData                = $this->_getEncryptionDataObject();

        //Override the information
        $encryptData['AutoStart']   = '0';
        $encryptData['URLSuccess']  = Mage::getUrl('paymentoperator/callback_ideal/success', array('_forced_secure' => true));
        $encryptData['URLFailure']  = Mage::getUrl('paymentoperator/callback_ideal/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']   = Mage::getUrl('paymentoperator/callback_ideal/notify', array('_forced_secure' => true));
    }
}