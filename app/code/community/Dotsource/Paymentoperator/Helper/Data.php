<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Data
    extends Mage_Core_Helper_Abstract
{

    /** Try to get the model from the right section */
    const AUTO              = 1;

    /** Try to get the model from the frontend section */
    const FRONTEND          = 2;

    /** Try to return the model from the backend section */
    const BACKEND           = 3;


    /** Holds the paymentoperator encryption model */
    protected $_encryptor   = null;


    /**
     * Return the encryptor.
     *
     * @return Dotsource_Paymentoperator_Model_Encryption
     */
    public function getEncryptor()
    {
        if (is_null($this->_encryptor)) {
            $this->_encryptor = Mage::getModel('paymentoperator/encryption');
            $this->_encryptor->setHelper($this);
        }

        return $this->_encryptor;
    }


    /**
     * Return the current in use transaction model.
     *
     * @param int $transactionId
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function getTransactionModel(
        $transactionId  = null,
        $section        = self::AUTO
    )
    {
        if (self::AUTO == $section) {
            if ($this->isFrontend()) {
                return $this->getFrontendTransactionModel($transactionId);
            } else {
                return $this->getBackendTransactionModel($transactionId);
            }
        } elseif (self::FRONTEND == $section) {
            return $this->getFrontendTransactionModel($transactionId);
        } elseif (self::BACKEND == $section) {
            return $this->getBackendTransactionModel($transactionId);
        }

        //Something is wrong
        Mage::throwException(
            'No logic available for this area %s to get the transaction model',
            Mage::getDesign()->getArea()
        );
    }


    /**
     * Try to load a transaction model from the given transaction id
     * or get the transaction model from the session or create a new one.
     *
     * @param int $transactionId
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function getFrontendTransactionModel($transactionId = null)
    {
        $session = Mage::getSingleton('paymentoperator/session');

        if (null !== $transactionId) {
            $session->setTransactionModelId($transactionId);
        }

        return $session->getTransactionModel();
    }


    /**
     * Logic for saving the current transaction model in the backend area.
     *
     * @param mixed $transactionId
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function getBackendTransactionModel($transactionId = null)
    {
        //Try to get the model from the registry
        $transactionModel = $this->getTransactionModelFromRegistry();

        if (!empty($transactionId)) {
            //Check if we have the right model
            if (!$this->_isValidTransactionModel($transactionModel, $transactionId)) {
                //Load the model from the reference id
                $transactionModel = Mage::getModel('paymentoperator/transaction')
                                        ->load($transactionId);

                //Add the model to the registry
                $this->addTransactionModelToRegistry($transactionModel);
            }
        } else { //No transaction id is given we can only return the current model from the registry
            if (!$this->_isValidTransactionModel($transactionModel)) {
                Mage::throwException("Can't get any transaction model.");
            }
        }

        //Return the valid model
        return $transactionModel;
    }


    /**
     * Add the transaction model to the registry.
     *
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function getTransactionModelFromRegistry()
    {
        return Mage::registry('transactionModel');
    }


    /**
     * Add the transaction model to the registry.
     *
     * @param Dotsource_Paymentoperator_Model_Transaction $model
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function addTransactionModelToRegistry(Dotsource_Paymentoperator_Model_Transaction $model)
    {
        //Check if the transaction model is valid
        if ($this->_isValidTransactionModel($model)) {
            Mage::unregister('transactionModel');
            Mage::register('transactionModel', $model);
        } else {
            Mage::throwException("The given transaction model is not valid.");
        }

        return $model;
    }


    /**
     * Check if a Transaction model is valid
     *
     * @param mixed $model
     * @param int $transactionId
     */
    protected function _isValidTransactionModel($model, $transactionId = null)
    {
        return !empty($model)
            && $model instanceof Dotsource_Paymentoperator_Model_Transaction
            && (null === $transactionId || $transactionId == $model->getId());
    }


    /**
     * Check if a customer model is available.
     *
     * @return boolean
     */
    public function isCustomerAvailable($section = self::AUTO)
    {
        if (self::AUTO == $section) {
            if ($this->isFrontend()) {
                return  Mage::getSingleton('customer/session')->isLoggedIn();
            } else {
                $section    = self::BACKEND;
            }
        }

        //Get the customer
        $customer    = $this->getCustomer($section);

        //check for valid customer
        return !empty($customer)
            && $customer instanceof Mage_Customer_Model_Customer
            && $customer->hasData();
    }


    /**
     * Return the customer object from the quote.
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer($section = self::AUTO)
    {
        return $this->getQuote($section)->getCustomer();
    }


    /**
     * Return the quote from the session.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote($section = self::AUTO)
    {
        return $this->getCheckoutSession($section)->getQuote();
    }


    /**
     * Return the current using session.
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession($section = self::AUTO)
    {
        if (self::AUTO == $section) {
            if ($this->isFrontend()) {
                return $this->getFrontendCheckoutSession();
            } else {
                return $this->getBackendCheckoutSession();
            }
        } elseif (self::FRONTEND == $section) {
            return $this->getFrontendCheckoutSession();
        } elseif (self::BACKEND == $section) {
            return $this->getBackendCheckoutSession();
        }

        //Something is wrong
        Mage::throwException(
            'No logic available for this area %s to get the transaction model',
            Mage::getDesign()->getArea()
        );
    }


    /**
     * Log the given data.
     *
     * @param mixed $data
     * @param string $prefix
     * @param array $stripTags
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    public function Log($data, $prefix = null, array $stripTags = array())
    {
        //Only debug in demo mode
        if (!$this->isDemoMode()) {
            return $this;
        }

        //If we got an object it's better to clone
        if (is_object($data)) {
            $data = clone $data;
        }

        //Setup the prefix
        if (is_string($prefix)) {
            $prefix = "\n$prefix:\n";
        } else {
            $prefix = '';
        }

        //If we got an Varien_Object (includes Dotsource_Paymentoperator_Object)
        if ($data instanceof Varien_Object) {
            //Get the array
            $dataArray = $data->getData();

            //change the key case to lower case
            $dataArray = array_change_key_case($dataArray, CASE_LOWER);

            //Additional logging handling for Dotsource_Paymentoperator_Object objects
            if ($data instanceof Dotsource_Paymentoperator_Object) {
                //Decrypt the request
                if ($data->hasEncryptor()
                    && array_key_exists('data', $dataArray)
                    && is_string($dataArray['data'])
                ) {
                    $dataArray = $data->getEncryptor()->decrypt($dataArray['data']);
                    $dataArray = array_change_key_case($dataArray, CASE_LOWER);
                }

                //Decrypt the userdata
                if (array_key_exists('userdata', $dataArray)) {
                    if (is_string($dataArray['userdata'])) {
                        $tmp = new Dotsource_Paymentoperator_Object();
                        $tmp->setData(base64_decode($dataArray['userdata']));

                        $dataArray['userdata'] = $tmp->getData();
                    } elseif ($dataArray['userdata'] instanceof Varien_Object) {
                        $dataArray['userdata'] = $dataArray['userdata']->getData();
                    }

                    //lower case for user data
                    if (is_array($dataArray['userdata'])) {
                        $dataArray['userdata'] = array_change_key_case($dataArray['userdata'], CASE_LOWER);
                    }
                }
            }

            //Override
            $data = $dataArray;
        }

        //Strip dangerous tags before log the data
        $data = $this->getConverter()->stripDangerousTags($data, $stripTags);

        //Convert the array to string
        if (is_array($data)) {
            $data = $this->_getString($data);
        }

        //We need an string to log
        Mage::log("$prefix$data\n", null, 'paymentoperator_connection.log', true);

        return $this;
    }


    /**
     * Helper for process array in array constructs.
     *
     * @param array $data
     * @param string $glue
     * @param string $prefix
     * @param string $suffix
     * @param string $appendPrefix
     * @return string
     */
    protected function _getString(array $data, $glue = ' = ', $prefix = "\t", $suffix = "\n", $appendPrefix = null)
    {
        //Holds the string
        $string = "";

        //Use the current prefix as append prefix for the next recursion
        if (null === $appendPrefix) {
            $appendPrefix = $prefix;
        }

        foreach ($data as $key => $value) {
            $valueFromRecursion = false;
            $key = $this->getConverter()->convertToUtf8($key);

            //Convert logic
            if ($value instanceof Varien_Object) {
                $value = $value->getData();
            }

            if (is_array($value)) {
                $valueFromRecursion = true;
                //Recursion add the suffix as prefix for the recursion string
                $value = $this->_getString($value, $glue, $prefix . $appendPrefix, $suffix, $appendPrefix);
            } else if (is_object($value)) {
                $value = 'Object class ' . get_class($value);
            } else if (is_resource($value)) {
                $value = 'Resource type ' . get_resource_type($value);
            } else {
                $value = $this->getConverter()->convertToUtf8($value);
            }

            //Create the string
            if (!$valueFromRecursion) {
                $string .= $prefix . '[' . $key . ']' . $glue . $value . $suffix;
            } else {
                $string .= $prefix . '[' . $key . ']' . $glue . $suffix . $value;
            }
        }

        return $string;
    }


    /**
     * Reactivate the given quote id ($quoteId) in the given section ($section).
     *
     * If no quote if was given the method will reactivate the quote id which is
     * placed in the checkout session as 'last_quote_id'.
     *
     * If a active quote should be replaced the $forceReplace is need to set true.
     * The current quote will deactivated and replaced with the given quoted id.
     *
     * @param int $quoteId
     * @param int $section
     * @param boolean $forceReplace
     * @return unknown
     */
    public function restoreQuote($quoteId = null, $section = self::AUTO, $forceReplace = false)
    {
        //Checkout session
        $session = $this->getCheckoutSession($section);

        //Try to get the last used quote id
        if (null === $quoteId) {
            $quoteId = $session->getLastQuoteId();
        }

        //We need a quote id for restoring
        if (empty($quoteId)) {
            return $this;
        }

        //Get the current quote
        $currentQuote = $session->getQuote();
        $validCurrentQuote = $currentQuote instanceof Mage_Sales_Model_Quote
            && (boolean)$currentQuote->getId();

        //Check for reactivating the current quote
        if ($validCurrentQuote && $quoteId == $currentQuote->getId()) {
            //Reactive the current quote if needed
            if (!$currentQuote->getData('is_active')) {
                $currentQuote->setData('is_active', 1)->save();
            }
        } else {
            //If we should forced to replace, deactivate the current quote if needed
            if ($forceReplace && $validCurrentQuote && $currentQuote->getData('is_active')) {
                $currentQuote->setData('is_active', 0)->save();
            }

            //Reactivate the given quote id only if we don't have a valid current quote
            //or the current quote if not activate
            if (!$validCurrentQuote || !$currentQuote->getData('is_active')) {
                $restoreQuote = Mage::getModel('sales/quote')->load($quoteId);

                //Reactivate the last quote if possible
                if ($restoreQuote instanceof Mage_Sales_Model_Quote && $restoreQuote->getId()) {
                    //Reactivate
                    $restoreQuote->setData('is_active', 1)->save();

                    //Set in current session
                    $session->replaceQuote($restoreQuote);
                }
            }
        }

        return $this;
    }


    /**
     * Return the frontend quote.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getFrontendQuote()
    {
        return $this->getFrontendCheckoutSession()->getQuote();
    }


    /**
     * Return the backend quote.
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getBackendQuote()
    {
        return $this->getBackendCheckoutSession()->getQuote();
    }


    /**
     * Return the frontend checkout session.
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getFrontendCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }


    /**
     * Return the backend checkout session.
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    public function getBackendCheckoutSession()
    {
        return Mage::getSingleton('adminhtml/sales_order_create')->getSession();
    }


    /**
     * Return true if the design area is frontend and the the
     * cronjob flag are not set.
     *
     * @return boolean
     */
    public function isFrontend()
    {
        return Mage_Core_Model_App_Area::AREA_FRONTEND == Mage::getDesign()->getArea()
            && !$this->isCronJob();
    }


    /**
     * Return if the current magento is in backend.
     *
     * @return boolean
     */
    public function isBackend()
    {
        return 'adminhtml' == Mage::getDesign()->getArea();
    }


    /**
     * Return true if the regitry key 'cronjob' is true.
     *
     * @return boolean
     */
    public function isCronJob()
    {
        return is_bool(Mage::registry('cronjob')) && Mage::registry('cronjob');
    }


    /**
     * Set the cronjob area the active in the registry.
     */
    public function enableCronJobArea()
    {
        Mage::register('cronjob', true, true);
    }


    /**
     * Return true if paymentoperator is in demo modus.
     *
     * @return boolean
     */
    public function isDemoMode()
    {
        return Mage::registry('paymentoperatorDemoMode')
            || (isset($_SERVER['PAYMENTOPERATOR_DEMO_MODE']) && $_SERVER['PAYMENTOPERATOR_DEMO_MODE']);
    }


    /**
     * Return true if the modul can use.
     *
     * @param boolean $ignoreDemoMode
     * @return boolean
     */
    public function isGlobalActive($ignoreDemoMode = false)
    {
        return ($this->isDemoMode() && !$ignoreDemoMode)
            || $this->isHttpsUrl(
                Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)
            );
    }


    /**
     * Return true if the given url begins with https.
     *
     * @param string $url
     * @return bool
     */
    public function isHttpsUrl($url)
    {
        return $this->isStringStartsWith(trim($url), 'https://');
    }


    /**
     * Checks if the haystack starts with the given needel.
     *
     * @param string $haystack
     * @param string $needel
     * @return boolean
     */
    public function isStringStartsWith($haystack, $needel)
    {
        return 0 === strcasecmp($needel, substr($haystack, 0, strlen($needel)));
    }


    /**
     * Return the paymentoperator converter.
     *
     * @return Dotsource_Paymentoperator_Helper_Converter
     */
    public function getConverter()
    {
        return Mage::helper('paymentoperator/converter');
    }


    /**
     * Return the paymentoperator configuration model.
     *
     * @return Dotsource_Paymentoperator_Helper_Config
     */
    public function getConfiguration()
    {
        return Mage::helper('paymentoperator/config');
    }


    /**
     * Return the payment helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Payment
     */
    public function getPaymentHelper()
    {
        return Mage::helper('paymentoperator/payment');
    }


    /**
     * Return the feature helper.
     *
     * @return  Dotsource_Paymentoperator_Helper_Feature
     */
    public function getFeatureHelper()
    {
        return Mage::helper('paymentoperator/feature');
    }

    /**
     * Return the depends model.
     *
     * @return  Dotsource_Paymentoperator_Helper_Depends
     */
    public function getDepends()
    {
        return Mage::helper('paymentoperator/depends');
    }

    /**
     * Indicates if the payment logos should be shown in the checkout
     * process beneath the payment method.
     *
     * @return  boolean
     */
    public function canShowPaymentLogo()
    {
        return true;
    }
}