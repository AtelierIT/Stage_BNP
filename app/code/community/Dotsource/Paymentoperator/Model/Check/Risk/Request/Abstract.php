<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Check_Risk_Request_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /** Holds if the request should use hmac */
    protected $_useHmac     = false;

    /** Holds the risk model */
    protected $_riskModel   = null;


    /**
     * Override to remove payment specific logic.
     */
    protected function _preProcessRequestData()
    {
        $request        = $this->_getRequestObject();
        $encryptData    = $this->_getEncryptionDataObject();

        //Add the merchant id
        $request['MerchantID'] = $this->_getHelper()->getConfiguration()->getPaymentSpecificMerchantFieldByCurrency(
            $this->_getRiskModel()->getCode(),
            $this->_getCurrencyCode(),
            'id'
        );

        //Set the encryption settings
        $encryptData->setEncryptionSettings(
            $this->_getHelper()->getConfiguration()->getPaymentSpecificMerchantFieldByMerchantId(
                $this->_getRiskModel()->getCode(),
                $request['MerchantID']
            )
        );
    }


    /**
     * Set the risk model.
     *
     * @param $risk
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Request_Abstract
     */
    public function setRiskModel(Dotsource_Paymentoperator_Model_Check_Risk_Abstract $risk)
    {
        $this->_riskModel = $risk;
        return $this;
    }


    /**
     * Return the risk model.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Abstract
     */
    protected function _getRiskModel()
    {
        return $this->_riskModel;
    }
}