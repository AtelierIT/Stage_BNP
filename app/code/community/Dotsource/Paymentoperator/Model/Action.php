<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Action
    extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('paymentoperator/action');
    }


    /**
     * Add the given connection in the paymentoperator action.
     *
     * @param   Dotsource_Paymentoperator_Model_Payment_Connection_Abstract  $container
     * @return  Dotsource_Paymentoperator_Model_Action
     */
    public function logRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request $request)
    {
        //Get the last used transaction models
        $transactionModel = $request->getPaymentoperatorTransactionModel();

        /* @var $response Dotsource_Paymentoperator_Object */
        $response = $request->getResponseModel()->getResponse();

        //Set the data and save the action
        $this->setTransactionId($transactionModel->getId())
            ->setRequestPayid($request->getPayid())
            ->setResponsePayid($request->getResponseModel()->getResponse()->getPayid())
            ->setXid($response->getXid())
            ->setAction($request->getRequestFile())
            ->setErrorCode($response->getCode())
            ->setErrorDescription($response->getDescription());

        //Set the error flag if something went wrong
        if ($request->getResponseModel()->hasError()) {
            $this->setError('1');
        } else {
            $this->setError('0');
        }
        return $this;
    }


    /**
     * Add the given response from the callback controller
     * to the paymentoperator action.
     *
     * @param Dotsource_Paymentoperator_Model_Payment_Response_Response $response
     * @param Mage_Payment_Model_Info $payment
     * @return Dotsource_Paymentoperator_Model_Action
     */
    public function logResponse(
        Dotsource_Paymentoperator_Model_Payment_Response_Response $response,
        Mage_Payment_Model_Info $payment,
        $requestPayid = null
    )
    {
        //Set the data and save the action
        $this->setTransactionId($payment->getPaymentoperatorTransactionId())
            ->setRequestPayid($requestPayid)
            ->setResponsePayid($response->getResponse()->getPayid())
            ->setXid($response->getResponse()->getXid())
            ->setAction($payment->getMethodInstance()->getCode())
            ->setErrorCode($response->getResponse()->getCode())
            ->setErrorDescription($response->getResponse()->getDescription());

        //Set the error flag if something went wrong
        if ($response->hasError()) {
            $this->setError('1');
        } else {
            $this->setError('0');
        }
        return $this;
    }


    /**
     * @see Mage_Core_Model_Abstract::_beforeSave()
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();

        //If new created set the created_at time to now
        if (!$this->getId()) {
            $this->setCreatedAt(now());
        }
        return $this;
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}