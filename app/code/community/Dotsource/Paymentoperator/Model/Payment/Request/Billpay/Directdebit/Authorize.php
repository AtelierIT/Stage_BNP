<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Directdebit_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Authorize
{

    /**
     * Return the used object for parsing the response.
     *
     * @return string
     */
    public function getResponseModelCode()
    {
        return "paymentoperator/payment_response_billpay_directdebit_authorize";
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::_getRequestData()
     */
    protected function _getRequestData()
    {
        parent::_getRequestData();

        //Add payment information
        /* @var $paymentMethod Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract */
        $encryptData                    = $this->_getEncryptionDataObject();
        $paymentMethod                  = $this->getPaymentMethod();

        $encryptData['AccOwner']        = $paymentMethod->getEftOwner();
        $encryptData['AccNr']           = $paymentMethod->getEftBan();
        $this->_addDangerousTag('AccNr');
        $encryptData['AccIBAN']         = $paymentMethod->getEftBcn();
    }
}