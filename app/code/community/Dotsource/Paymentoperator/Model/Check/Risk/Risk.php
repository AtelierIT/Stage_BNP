<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Risk
    extends Dotsource_Paymentoperator_Model_Check_Risk_Abstract
{

    /** Holds a flag if the risk logic should use the fallback payments */
    protected static $_useFallbackPayments      = false;

    /** Holds the risk check code */
    protected $_code                            = 'risk_check';

    /** Holds the request model path */
    protected $_requestModelPath                = 'paymentoperator/check_risk_request_risk';

    /** Holds the selected payments from the backend */
    protected $_configuredPaymentsByRisk        = null;

    /** Holds the fallback cases */
    protected $_fallbackCases                   = null;


    /**
     * Init the risk model.
     *
     * @param $payment
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Risk
     */
    public function init($payment)
    {
        //Set the given payment to the request model
        parent::init($payment);

        //Get the customer object and add to the storage if the customer object is available
        $customerObject = $this->_getRequestModel()->getOracle()->getCustomer();
        if ($customerObject) {
            $this->getStorageModel()->setPrimaryObject($customerObject, 'save');
        }

        //Config the rest of the storage system
        $this
            ->setStorageKey('paymentoperator_risk_check') //No dynamic key needed only one risk check available
            ->getStorageModel()
            ->setSecondaryObject(Mage::getSingleton('paymentoperator/session_risk'))
            ->getStorageHandler()
            ->addHandler(Mage::getModel('paymentoperator/check_risk_storage_backend_serialize'))
            ->addHandler(Mage::getModel('paymentoperator/check_risk_storage_backend_encrypt'));

        return $this;
    }


    /**
     * Process the risk request. The response is saved in the storage system.
     * To get the response use the getResponse-Method.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Risk
     */
    public function process()
    {
        //Check if we have a valid response model
        if ($this->getResponse()) {
            return;
        }

        //Check if we have the ability to send a request
        if ($this->isAllowToSendRequest()) {
            $this->_getConnection()->sendRequest($this->_getRequestModel());

            //Dispatch an event to notice that we have send a new risk request
            Mage::dispatchEvent(
                'paymentoperator_risk_request_was_sent',
                array(
                    'response' => $this->getResponse()
                )
            );
        }

        //Get the response
        $response = $this->getResponse();

        //Check if we have an error
        if ($response && !$response->hasError()) {
            //Set the check time in the request
            $this->_getRequestModel()->getResponseModel()->getResponse()->setCheckTime(time());

            $responseSaveData           = $this->_getRequestModel()->getResponseModel()->getResponse()->getData();
            $responseSaveData['amount']	= $this->_getHelper()->getQuote()->getBaseGrandTotal();

            //Store the request in the storage system
            $this->getStorageModel()->setData(
                $this->getStorageKey(),
                $responseSaveData
            );
        }

        return $this;
    }


    /**
     * Return true if the current payment can use. If the method return false
     * the user is not allowed to use the payment method. If the return value
     * is null we don't know if the user is allowed to use the payment method.
     *
     * @param mixed $payment
     * @return boolean
     */
    public function isPaymentAvailable($payment = null)
    {
        //Try to get the payment code from the object
        if (is_object($payment)) {
            if ($payment instanceof Mage_Payment_Model_Method_Abstract) {
                $payment = $payment->getCode();
            } elseif ($payment instanceof Mage_Payment_Model_Info) {
                $payment = $payment->getMethodInstance()->getCode();
            } elseif ($payment instanceof Mage_Sales_Model_Order
                || $payment instanceof Mage_Sales_Model_Quote
            ) {
                $payment = $payment->getPayment()->getMethodInstance()->getCode();
            } else {
                Mage::throwException(
                    'Can\'t get the payment method from the given object "' . get_class($payment) . '"'
                );
            }
        }

        //Validate the payment as string
        if (!is_string($payment)) {
            Mage::throwException('Can\'t get the payment code from the given data.');
        }

        //All payment codes are in lower case
        $payment            = strtolower($payment);
        $configuredPayments = $this->_getConfiguredPaymentsByRisk();
        $address            = $this->_getRequestModel()->getOracle()->getBillingAddress();

        /* @var $response Dotsource_Paymentoperator_Model_Check_Risk_Response_Response */
        $response           = $this->getResponse();

        //If the fallback system is active do a pre check of the risk environment
        if ($this->isFallbackActive()) {
            //Check if we need to return
            if (($this->isFallbackOnBillingAddressOutsideOfGermanyActive() && !$this->isAddressCountrySupported($address))
                || ($this->isFallbackOnMissingPaymentoperatorAddressCheckActive() && !$this->isAllowToSendRequest())
                || ($this->isFallbackOnUndefinedResponseActive() && $this->_isUseFallbackFlagActive())
            ) {
                return (boolean) $configuredPayments->getData("fallback/$payment");
            }
        }

        //Check for supported country and valid response
        if ($this->isAddressCountrySupported($address)
            && $this->isAllowToSendRequest()
            && $response
            && !$response->hasError()
        ) {
            return (boolean) ($response->isGreen()
                || $configuredPayments->getData("{$response->getMappedActionValue()}/$payment"));
        }

        //Undefined
        return null;
    }


    /**
     * Check all conditions if the risk model can process.
     *
     * @return boolean
     */
    public function isAvailable()
    {
        //Check the active status
        if (!parent::isAvailable()) {
            return false;
        }

        //Get the oracle
        $oracle = $this->_getRequestModel()->getOracle();

        //Check if the grand total is at least the minimal amount
        $diff = $oracle->getBaseGrandTotal() - $this->getConfigData('min_amount');
        if ($this->_getHelper()->getPaymentHelper()->isNegativeAmount($diff)) {
            return false;
        }

        //Check if we have a dob
        if (!$oracle->getDob()) {
            return false;
        }

        //Return false if the address are not supported and fallback for
        //not supported country are disabled
        if (!$this->isAddressCountrySupported($oracle->getBillingAddress())
            && !$this->isFallbackOnBillingAddressOutsideOfGermanyActive()
        ) {
            return false;
        }

        //This one holds the result from the is available event
        $result = new Varien_Object();
        $result->setIsAvailable(true);

        Mage::dispatchEvent(
            'paymentoperator_risk_check_isAvailable',
            array(
                'result'    => $result,
                'oracle'    => $oracle,
            )
        );

        //Return the the value from the result object
        return $result->getIsAvailable();
    }


    /**
     * Return true if all conditions ok to send a request to paymentoperator
     * to get a risk value.
     *
     * @return boolean
     */
    public function isAllowToSendRequest()
    {
        //Prepare data for the event
        $oracle = $this->_getRequestModel()->getOracle();

        //Only send a request if the country is supported
        if (!$this->isAddressCountrySupported($oracle->getBillingAddress())) {
            return false;
        }

        $result = new Varien_Object();
        $result->setIsAvailable(true);

        //Dispatch the event
        Mage::dispatchEvent(
            'paymentoperator_risk_check_isAllowToSendRequest',
            array(
                'result'    => $result,
                'oracle'    => $oracle,
            )
        );

        //Return the the value from the result object
        return $result->getIsAvailable();
    }


    /**
     * Return the configured payments in the backend for the risk values.
     *
     * @return Varien_Object
     */
    protected function _getConfiguredPaymentsByRisk()
    {
        if (null === $this->_configuredPaymentsByRisk) {
            //Get the select payments
            $yellowPayments     = strtolower($this->getConfigData('yellow_payments'));
            $redPayments        = strtolower($this->getConfigData('red_payments'));

            //Split the payments
            $yellowPayments     = explode(',', $yellowPayments);
            $redPayments        = explode(',', $redPayments);
            $payments           = new Varien_Object();

            //Check if we need to add the configured payments
            if ($redPayments && is_array($redPayments)) {
                $payments->setData('red', $this->_getConfiguredPaymentCodeArrayByRisk($redPayments));
            }

            //Same for yellow
            if ($yellowPayments && is_array($yellowPayments)) {
                $payments->setData('yellow', $this->_getConfiguredPaymentCodeArrayByRisk($yellowPayments));
            }

            //Add the fallback payments if the fallback system is active
            if ($this->isFallbackActive()) {
                $fallbackPayments   = strtolower($this->getConfigData('fallback_payments'));
                $fallbackPayments   = explode(',', $fallbackPayments);

                //Same for fallback payments
                if ($fallbackPayments && is_array($fallbackPayments)) {
                    $payments->setData('fallback', $this->_getConfiguredPaymentCodeArrayByRisk($fallbackPayments));
                }
            }

            //Set the configured object
            $this->_configuredPaymentsByRisk = $payments;
        }

        return $this->_configuredPaymentsByRisk;
    }


    /**
     * Format the payment codes as key with an valid value as value.
     *
     * @param array $payments
     * @return array
     */
    protected function _getConfiguredPaymentCodeArrayByRisk(array $payments)
    {
        $payments = array_flip($payments);

        foreach ($payments as $key => $value) {
            $payments[$key] = true;
        }

        return $payments;
    }


    /**
     * Return the check moment of the risk check.
     *
     * @string
     */
    protected function _getRiskCheckMoment()
    {
        return $this->getConfigData('check_moment');
    }


    /**
     * Return true if the risk check should use by order place.
     *
     * @return boolean
     */
    public function isRiskCheckAtOrderPlace()
    {
        return Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Checkposition::PLACE_ORDER
            === $this->_getRiskCheckMoment();
    }


    /**
     * Return true if the risk check should use by the payment methods.
     *
     * @return boolean
     */
    public function isRiskCheckAtPaymentsMethods()
    {
        return Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Checkposition::PAYMENT
            === $this->_getRiskCheckMoment();
    }


    /**
     * Return true if the fallback system are active for risk check.
     *
     * @return boolean
     */
    public function isFallbackActive()
    {
        return (boolean) $this->getConfigData('fallback_active');
    }


    /**
     * Check if the given fallback is active.
     *
     * @param string $case
     * @return boolean
     */
    protected function _isFallbackCaseActive($case)
    {
        return array_key_exists($case, $this->_getFallbackCases());
    }


    /**
     * Return an array with the fallback cases.
     *
     * @return array
     */
    public function _getFallbackCases()
    {
        if (null === $this->_fallbackCases) {
            $cases = $this->getConfigData('fallback_cases');
            $cases = explode(',', $cases);

            if ($cases && is_array($cases)) {
                $this->_fallbackCases = array_flip($cases);
            } else {
                $this->_fallbackCases = array();
            }
        }

        return $this->_fallbackCases;
    }


    /**
     * Return true if the fallback "response is undefined" is active.
     *
     * @return boolean
     */
    public function isFallbackOnUndefinedResponseActive()
    {
        return $this->isFallbackActive()
            && $this->_isFallbackCaseActive(Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Fallbackcases::UNDEFINED_RESPONSE);
    }


    /**
     * Return true if the fallback "billing address outside of germany" is active.
     *
     * @return boolean
     */
    public function isFallbackOnBillingAddressOutsideOfGermanyActive()
    {
        return $this->isFallbackActive()
            && $this->_isFallbackCaseActive(Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Fallbackcases::OUTSIDE_OF_GERMANY);
    }


    /**
     * Return true if the fallback "missing paymentoperator address check" is active.
     *
     * @return boolean
     */
    public function isFallbackOnMissingPaymentoperatorAddressCheckActive()
    {
        return $this->isFallbackActive()
            && $this->_isFallbackCaseActive(Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Fallbackcases::MISSING_ADDRESS_CHECK);
    }


    /**
     * Return true if the given address are supported from the risk check.
     *
     * @param $address
     * @return boolean
     */
    public function isAddressCountrySupported($address)
    {
        return $address
            && $address->getData()
            && 'de' === strtolower($address->getCountryModel()->getIso2Code());
    }


    /**
     * Return a boolean to check if we use the fallback flag.
     *
     * @return boolean
     */
    protected function _isUseFallbackFlagActive()
    {
        return self::$_useFallbackPayments;
    }


    /**
     * Set a flag if the risk method should use fallback payments.
     *
     * @param boolean $flag
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Risk
     */
    public function setUseFallbackFlag($flag)
    {
        self::$_useFallbackPayments = (boolean) $flag;
        return $this;
    }
}