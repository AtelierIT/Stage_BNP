<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Payment
    extends Mage_Core_Helper_Abstract
{

    /** Holds the key for the redirect message flag */
    const REDIRECT_MSG_KEY              = "_paymentoperator_info_block_show_redirect_msg";

    /** Holds the prefix for the disable paymentss */
    protected $_disablePaymentKey       = "disable_payments";

    /** Holds the disable time for the current session */
    protected $_disableTimeForCheckout  = 86400;


    /**
     * Add general information to the transaction payment model.
     *
     * @param Mage_Payment_Model_Info $payment
     * @param Dotsource_Paymentoperator_Model_Payment_Response_Response $response
     */
    public function addTransactionInfoToPayment(
        Mage_Payment_Model_Info $payment,
        Dotsource_Paymentoperator_Model_Payment_Response_Response $response
    )
    {
        //Use the xid as transaction id
        $payment
            ->setPreparedMessage(Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENTOPERATOR_PREFIX)
            ->setTransactionId($response->getResponse()->getXid());

        //Add the payid to the additional payment information
        $payment->setTransactionAdditionalInfo(
            'payid',
            $response->getResponse()->getPayid()
        );
    }

    /**
     * Return true if the amount is zero.
     *
     * @param float $amount
     * @param float $lambda
     * @return boolean
     */
    public function isZeroAmount($amount, $lambda = 0.0001)
    {
        return !$this->isPositiveAmount($amount, $lambda)
            && !$this->isNegativeAmount($amount, $lambda);
    }

    /**
     * Check if an given amount if > 0.
     *
     * @param   float   $amount
     * @param   float   $lambda
     * @return  boolean
     */
    public function isPositiveAmount($amount, $lambda = 0.0001)
    {
        return ((float) $amount) > ((float) $lambda);
    }

    /**
     * Check if an given amount if < 0.
     *
     * @param   float   $amount
     * @param   float   $lambda
     * @return  boolean
     */
    public function isNegativeAmount($amount, $lambda = 0.0001)
    {
        return ((float) $amount) < -((float) $lambda);
    }

    /**
     * Return the backend error manager.
     *
     * @return  Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function getBackendErrorManager($special = null)
    {
        if (empty($special) || null === Mage::registry("error_{$special}_manager")) {
            return Mage::getModel('paymentoperator/error_manager')
                ->addHandler('message', 'message', array(), 1)
                ->addHandler('translate', 'translate', array('helper' => 'paymentoperator'), 2)
                ->addHandler('callback', 'callback', array(), 3)
                ->addHandler('session', 'session', array('session' => 'adminhtml/session'))
                ->addHandler('exception', 'exception', array(), 1000);
        }

        return Mage::registry("error_{$special}_manager")->reset();
    }

    /**
     * Return the frontend error manager.
     *
     * @return  Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function getFrontendErrorManager()
    {
        return Mage::getModel('paymentoperator/error_manager')
            ->addHandler('message', 'message', array(), 1)
            ->addHandler('translate', 'translate', array('helper' => 'paymentoperator'), 2)
            ->addHandler('callback', 'callback', array(), 3)
            ->addHandler('exception', 'exception', array(), 1000);
    }

    /**
     * Return the error manager in depends of the current section frontend/backend.
     *
     * @param   string|null $special
     * @return  Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function getPaymentErrorManager($special = null)
    {
        //Check for special error manager first
        if ($special && Mage::registry("error_{$special}_manager")) {
            return Mage::registry("error_{$special}_manager")->reset();
        }

        //Get the right error manager
        if ($this->_getHelper()->isFrontend()) {
            $manager = $this->getFrontendErrorManager();
        } else {
            $manager = $this->getBackendErrorManager();
        }

        //Check support for the new exception class (Mage_Payment_Model_Info_Exception),
        //this class can use in frontend and backend.
        //For validation errors is Mage_Payment_Exception the better choice (getPaymentValidationErrorHandler).
        if (@class_exists('Mage_Payment_Model_Info_Exception')) {
            $manager->updateHandler("exception", "setModule", "Mage_Payment_Model_Info");
        }

        return $manager;
    }

    /**
     * Return a frontend payment validation error handler.
     *
     * @param   string|null                             $field
     * @return  Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function getPaymentValidationErrorHandler($field = null)
    {
        $manager = $this->getFrontendErrorManager()
            ->updateHandler("exception", "setModule", "Mage_Payment");

        //Show the error message to a referencing field
        if (null !== $field) {
            $manager->updateHandler("exception", "setCode", $field);
        }

        return $manager;
    }

    /**
     * Set the disable payment keys in the current checkout session for one day.
     *
     * @param string $paymentCode
     * @param int $area
     */
    public function disablePaymentForCheckoutSession($paymentCode, $area = Dotsource_Paymentoperator_Helper_Data::AUTO)
    {
        //Get the session
        $session            = $this->_getHelper()->getCheckoutSession($area);
        $disabledPayments   = array();

        //Check for array
        if ($session->hasData($this->_disablePaymentKey)) {
            $data = $session->getData($this->_disablePaymentKey);

            //Recreate the array
            if (!is_array($data) || empty($data)) {
                $disabledPayments = array();
            } else {
                $disabledPayments = $data;
            }
        }

        //Save the disable key and the next avilable time
        $disabledPayments[$paymentCode] = time() + $this->_disableTimeForCheckout;

        //Save the new disable payment keys
        $session->setData($this->_disablePaymentKey, $disabledPayments);
    }

    /**
     * Check if the given payment code is disabled for the current checkout
     * session.
     *
     * @param string $paymentCode
     * @param int $area
     * @return boolean
     */
    public function isPaymentDisabled($paymentCode, $area = Dotsource_Paymentoperator_Helper_Data::AUTO)
    {
        //Get the session
        $session = $this->_getHelper()->getCheckoutSession($area);

        //Check for array
        if ($session->hasData($this->_disablePaymentKey)) {
            $data = $session->getData($this->_disablePaymentKey);

            //Recreate the array
            if (!empty($data) && is_array($data) && array_key_exists($paymentCode, $data)) {
                return $data[$paymentCode] > time();
            }
        }

        return false;
    }

    /**
     * Clear the payment deactivation in the checkout session.
     *
     * @param int $area
     */
    public function clearPaymentDeactivation($area = Dotsource_Paymentoperator_Helper_Data::AUTO)
    {
        //Get the session
        $session = $this->_getHelper()->getCheckoutSession($area);

        //Check if we have the key
        if ($session->hasData($this->_disablePaymentKey)) {
            //Remove the data
            $session->unsetData($this->_disablePaymentKey);
        }
    }

    /**
     * Return the supported language for the given country code and payment.
     *
     * @param   Mage_Payment_Model_Method_Abstract  $payment
     * @param   string                              $countryCode
     * @return  string
     */
    public function getPaymentgateLanguage(Mage_Payment_Model_Method_Abstract $payment, $countryCode = null)
    {
        //Resolve the default country code from the current store
        if (null === $countryCode) {
            /* @var $helper Mage_Core_Helper_Data */
            $helper = Mage::helper('core');
            if (method_exists($helper, 'getDefaultCountry')) {
                $countryCode = $helper->getDefaultCountry();
            } else {
                //Bridge for older magento versions
                $countryCode = Mage::getStoreConfig(
                    Mage_Core_Model_Locale::XML_PATH_DEFAULT_COUNTRY
                );
            }
        }

        //Prepare the code
        $countryCode = strtolower(trim($countryCode));

        //Fallback is english
        switch ($countryCode) {
            case 'de';
            case 'en';
            case 'al';
            case 'dk';
            case 'fr';
            case 'hu';
            case 'it';
            case 'jp';
            case 'nl';
            case 'no';
            case 'pl';
            case 'pt';
            case 'ru';
            case 'se';
            case 'sk';
            case 'sl';
            case 'tr';
            case 'cz';
                return $countryCode;
            case 'es';
            case 'sp';
                return 'es';
            case 'cn':
                return 'zh';
            default:
                return 'en';
        }
    }

    /**
     * Return true if the we show global the payment info redirect message.
     *
     * @return boolean
     */
    public function getIsShowRedirectMessageActiveOnPaymentInfoBlock()
    {
        return (boolean) Mage::registry(self::REDIRECT_MSG_KEY);
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