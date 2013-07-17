<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Request_Risk
    extends Dotsource_Paymentoperator_Model_Check_Risk_Request_Abstract
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'scoring.aspx';
    }


    /**
     * Return special risk response model.
     *
     * @return string
     */
    public function getResponseModelCode()
    {
        return 'paymentoperator/check_risk_response_response';
    }


    /**
     * Setup the given response model as response for the current request object.
     * @param $response
     */
    public function setResponseModel(Dotsource_Paymentoperator_Model_Payment_Response_Response $response)
    {
        $this->_responseModel = $response;
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Global data
        $oracle         = $this->getOracle();
        $encryptData    = $this->_getEncryptionDataObject();
        $riskModel      = $this->_getRiskModel();

        //Informations we need
        $billingAddress = $oracle->getBillingAddress();
//        $street         = $this->_getHelper()->getConverter()->splitStreet($billingAddress);

        //Fill the request data
        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['OrderDesc']       = $encryptData['TransID'];

        $encryptData['Vorname']         = $billingAddress->getFirstname();
        $encryptData['Name']            = $billingAddress->getLastname();
        $encryptData['GebDat']          = $oracle->getDob('dd.MM.yyyy');

        $encryptData['Strasse']         = $billingAddress->getStreet1();

        //TODO: Check if we need to fix?
//        $encryptData['Strasse']         = $street->getStreetName();
//        $encryptData['HNo']             = $street->getStreetNumber();

        $encryptData['PLZ']             = $billingAddress->getPostcode();
        $encryptData['Ort']             = $billingAddress->getCity();
        $encryptData['Auskunfteien']    = $riskModel->getConfigData('inquiry_offices');
    }
}