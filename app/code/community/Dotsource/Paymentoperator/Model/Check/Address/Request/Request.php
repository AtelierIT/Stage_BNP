<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.01.2011 09:59:55
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Address_Request_Request
    extends Dotsource_Paymentoperator_Model_Check_Risk_Request_Abstract
{

    /**
     * Dynamic usage of response model name.
     *
     * @var string
     */
    protected $_responseModelCode = 'paymentoperator/payment_response_response';


    /**
     * Return the file name.
     *
     * @return string
     */
    public function getRequestFile()
    {
        return 'AddressCheck.aspx';
    }


    /**
     * Add data to request object.
     *
     */
    protected function _getRequestData()
    {
        $encryptData    = $this->_getEncryptionDataObject();
        $address        = $this->_getRiskModel()->getCustomerCheckAddress();
        $street         = $this->_getHelper()->getConverter()->splitStreet($address);

        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['OrderDesc']       = $encryptData['TransID'];
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();

        $encryptData['Name']            = $address->getLastname();
        $encryptData['Vorname']         = $address->getFirstname();
        $encryptData['Strasse']         = $street->getStreetName();
        $encryptData['HNo']             = $street->getStreetNumber();
        $encryptData['PLZ']             = $address->getPostcode();
        $encryptData['Ort']             = $address->getCity();
    }
}