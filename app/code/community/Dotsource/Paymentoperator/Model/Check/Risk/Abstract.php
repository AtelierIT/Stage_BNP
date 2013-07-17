<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Check_Risk_Abstract
    extends Varien_Object
{

    /** Holds the global code prefix */
    protected $_codePrefix          = 'paymentoperator_risk';

    /** Holds the risk check code */
    protected $_code                = null;

    /** Holds the storage key for the storage system. */
    protected $_storageKey          = null;

    /** Holds the model path to the storage model */
    protected $_storageModelPath    = 'paymentoperator/check_risk_storage_storage';

    /** Holds the storage model */
    protected $_storageModel        = null;

    /** Holds the request model path */
    protected $_requestModelPath    = null;

    /** Holds the request model */
    protected $_requestModel        = null;

    /** Holds a previous response */
    protected $_previousResponse    = null;


    /**
     * Process the risk request. If a storage system is used
     * the response will saved by the storage system.
     */
    abstract public function process();


    /**
     * Init the the model.
     *
     * @param mixed payment
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Abstract
     */
    public function init($payment)
    {
        //Configure the request model
        $this->_getRequestModel()
            ->setPayment($payment)
            ->setRiskModel($this);

        return $this;
    }


    /**
     * Return a valid previous response.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Response_Response || null
     */
    public function getPreviousResponse()
    {
        if (null === $this->_previousResponse) {
            //Default value for no valid previous response
            $responseModel = false;

            //Try to get previous from the storage
            $data = $this->getStorageModel()->getData($this->getStorageKey());

            //Check if the data are valid from a previous request
            if ($data && is_array($data) && $this->_isPreviousResponseValid($data)) {
                /* @var $responseModel Dotsource_Paymentoperator_Model_Check_Risk_Response_Response */
                $responseModel = Mage::getModel($this->_getRequestModel()->getResponseModelCode());

                //Fill the response model with the old data
                $responseModel->setResponse($data, false);

                //If the previous response has an error we don't use it
                if ($responseModel->hasError()) {
                    $responseModel = false;
                }
            }

            //Set the data
            $this->_previousResponse = $responseModel;
        }

        //For all valid valued return the data
        if ($this->_previousResponse) {
            return $this->_previousResponse;
        }

        //Return null for all bad values
        return null;
    }


    /**
     * Return true if an old response can reuse.
     *
     * @param array $data
     * @return boolean
     */
    protected function _isPreviousResponseValid(array $data)
    {
        $baseGrandTotal = $this->_getHelper()->getQuote()->getBaseGrandTotal();

        // New amount is greater than the last checked one.
        if (isset($data['amount'])
            && $this->_getHelper()->getPaymentHelper()->isPositiveAmount($baseGrandTotal - $data['amount'])
        ) {
            return false;
        }


        //Invalid value
        $checkTime = 0;

        //Try to get the check time from the response (given $data array)
        if (isset($data['check_time'])) {
            $checkTime = $data['check_time'];
        }

        //Check for valid times
        if (!$checkTime || !$this->getConfigData('invalid_days')) {
            return false;
        }

        return ($checkTime + ($this->getConfigData('invalid_days') * 24 * 60 * 60)) >= time();
    }


    /**
     * Return a response model. This method return a response model from
     * a previous request or from the current request if the process method
     * was called.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Response_Response || null
     */
    public function getResponse()
    {
        //Return the previous response
        $responseModel = $this->getPreviousResponse();
        if ($responseModel instanceof Dotsource_Paymentoperator_Model_Payment_Response_Response) {
            return $responseModel;
        }

        //Check if the current request model has response
        $responseModel = $this->_getRequestModel()->getResponseModel();
        if ($responseModel instanceof Dotsource_Paymentoperator_Model_Payment_Response_Response) {
            return $responseModel;
        }

        return null;
    }


    /**
     * Return true if we have a valid response.
     *
     * @return boolean
     */
    public function hasResponse()
    {
        return $this->getResponse() instanceof Dotsource_Paymentoperator_Model_Payment_Response_Response;
    }


    /**
     * Sync the given key. If $shouldSave is true the configured save method
     * from the primary object will called.
     *
     * @param boolean $shouldSave
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Abstract
     */
    public function sync($shouldSave = false)
    {
        $this->getStorageModel()->sync($this->getStorageKey(), $shouldSave);
        return $this;
    }


    /**
     * Retrieve information from risk configuration.
     *
     * @param   string $field
     * @param   mixed $storeId
     * @return  mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }

        return Mage::getStoreConfig(
            "{$this->_getCodePrefix()}/{$this->getCode()}/{$field}", $storeId
        );
    }


    /**
     * Return true if the risk model is active.
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->_getHelper()->isGlobalActive()
            && (boolean) $this->getConfigData('active');
    }


    /**
     * Check all conditions if the risk model can process.
     *
     * @return boolean
     */
    public function isAvailable()
    {
        return $this->isActive();
    }


    /**
     * Return the code prefix.
     *
     * @return string
     */
    protected function _getCodePrefix()
    {
        if (null === $this->_codePrefix) {
            throw new Exception('Missing property "_codePrefix".');
        }

        return $this->_codePrefix;
    }


    /**
     * Return the risk code.
     *
     * @return string
     */
    public function getCode()
    {
        if (null === $this->_code) {
            throw new Exception('Missing property "_code".');
        }

        return $this->_code;
    }


    /**
     * Return the storage model.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    public function getStorageModel()
    {
        if (null === $this->_storageModel) {
            $this->_storageModel = Mage::getModel($this->_getStorageModelPath());
        }

        return $this->_storageModel;
    }


    /**
     * Return the request model path.
     *
     * @return string
     */
    protected function _getStorageModelPath()
    {
        if (null === $this->_storageModelPath) {
            throw new Exception('Missing property "_storageModelPath".');
        }

        return $this->_storageModelPath;
    }


    /**
     * Return the request model path.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Request_Abstract
     */
    protected function _getRequestModel()
    {
        if (null === $this->_requestModel) {
            $this->_requestModel = Mage::getModel($this->_getRequestModelPath());
        }

        return $this->_requestModel;
    }


    /**
     * Return the request model path.
     *
     * @return string
     */
    protected function _getRequestModelPath()
    {
        if (null === $this->_requestModelPath) {
            throw new Exception('Missing property "_requestModelPath".');
        }

        return $this->_requestModelPath;
    }


    /**
     * Holds the storage key.
     *
     * @param $key
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Abstract
     */
    public function setStorageKey($key)
    {
        $this->_storageKey = $key;
        return $this;
    }


    /**
     * Return the storage key.
     *
     * @return string
     */
    public function getStorageKey()
    {
        return $this->_storageKey;
    }


    /**
     * Reset the previous response.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Abstract
     */
    protected function _cleanPreviousResponse()
    {
        $this->_previousResponse = null;
        return $this;
    }


    /**
     * Return the connection model.
     *
     * @return Dotsource_Paymentoperator_Model_Connection
     */
    protected function _getConnection()
    {
        return Mage::getModel('paymentoperator/connection');
    }


    /**
     * Return the module helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}