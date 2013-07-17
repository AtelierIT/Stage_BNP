<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Check
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /** Deactivate sending the hmac */
    protected $_useHmac     = false;

    /** Deactivate logging the request */
    protected $_logRequest  = false;


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'giropayblz.aspx';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getResponseModelCode()
     *
     * @return string
     */
    public function getResponseModelCode()
    {
       return 'paymentoperator/payment_response_giropay_check';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request objects
        $request        = $this->_getRequestObject();
        $encryptData    = $this->_getEncryptionDataObject();

        //Fill the request data to the non encrypt
        $encryptData['MerchantID']  = $request['MerchantID'];
    }
}