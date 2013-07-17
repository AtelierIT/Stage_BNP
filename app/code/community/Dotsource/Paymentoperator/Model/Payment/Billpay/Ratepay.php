<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay
    extends Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract
{

    /**
     * Holds the key for the payment plan.
     */
    const KEY_BILLPAY_PAYMENT_PLAN                  = 'billpay_payment_plan';


    /**
     * Holds a flag that indicates that the select term should not validate.
     * @var boolean
     */
    protected static $_ignoreTermValidationFlag     = false;


    /** Holds the payment code */
    const CODE                                      = "paymentoperator_billpay_ratepay";
    protected $_code                                = self::CODE;

    /** Holds the block source path */
    protected $_formBlockType                       = 'paymentoperator/form_billpay_ratepay';

    /** Holds the info source path */
    protected $_infoBlockType                       = 'paymentoperator/info_billpay_ratepay';

    /** Holds the path to the request models */
    protected $_requestModelInfo                    = 'paymentoperator/payment_request_billpay_ratepay_';

    /** This payment method is not available for business customers. */
    protected $_isCompanyAllowed                    = false;

    /**
     * Holds the result for the method call getPaymentPlan().
     * @var array|null
     */
    protected $_cacheGetPaymentPlan                 = null;


    /**
     * Return the billpay action.
     *
     * @return  string
     */
    function getBillpayAction()
    {
        return "3";
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::assignData()
     */
    public function assignData($data)
    {
        //Remove a cached payment plan
        $this->resetPaymentPlanCache();

        //Do the normal process
        parent::assignData($data);
    }

    /**
     * Do the parent validation first. After this request the rates from paymentoperator.
     *
     * @return  Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay
     */
    public function validate()
    {
        parent::validate();

        //Need to recalculate the rates?
        if ($this->getIsRatesRequestNeeded()) {
            //Recalculation of rates are only allowed in the payment view
            if (!self::isIgnoreTermValidationFlagActive()) {
                $this->_getHelper()->getPaymentHelper()
                    ->getPaymentValidationErrorHandler($this->getFieldName(self::KEY_TERM))
                    ->processMessage('Please recalculate your rates and select a new one.');
            }

            //Do the authorize request to get all rates
            $request = $this->_createRequestModel('authorize', $this);
            $request->setAmount($this->getOracle()->getBaseGrandTotal());
            $this->_getConnection()->sendRequest($request);

            //Set the request data to the session
            $responseValide = $this->getBillpaySession()->setRates($request->getResponseModel());

            //Do the error handling here
            if (!$responseValide) {
                $this->_getHelper()->getPaymentHelper()
                    ->getPaymentValidationErrorHandler("p_method_{$this->getCode()}")
                    ->processErrorCode($request, $this->getCode());
            }
        }

        //If the selected terms was validated we are able to sync the selected rate to the payment
        if (!self::isIgnoreTermValidationFlagActive()) {
            $rates = $this->getBillpaySession()->getRates();
            $this->setPaymentPlan($rates[$this->getTerm()]);
        }

        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::_billpayAuthorize()
     *
     * @param   Varien_Object                                       $payment
     * @param   float                                               $amount
     * @param   boolean                                             $setTransactionInformsations
     * @return  Dotsource_Paymentoperator_Model_Payment_Response_Response
     */
    protected function _billpayAuthorize(
        Varien_Object $payment,
        $amount,
        $setTransactionInformsations = true
    )
    {
        //Get the request model
        $request = $this->_createRequestModel('authorize_finish', $payment);

        //No need to close the transaction in any case (auth, capture)
        if ($setTransactionInformsations) {
            $payment->setIsTransactionClosed(false);
        }

        //Set the amount
        $request->setAmount($amount);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            if ($setTransactionInformsations) {
                $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                    $payment,
                    $request->getResponseModel()
                );
            }

            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_authorize_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));

            //Return the request model
            return $request->getResponseModel();
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('authorize')
                ->addHandler(
                    'callback',
                    'paymentoperator_delete_transaction_callback',
                    array(
                        'callback' => array(
                            Mage::getSingleton('paymentoperator/observer'),
                            'deletePaymentoperatorTransaction',
                        )
                    )
                )
                ->processErrorCode($request, $this->getCode());
        }
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::_billpayCapture()
     *
     * @param   Varien_Object                               $payment
     * @param   float                                       $amount
     * @param   Mage_Sales_Model_Order_Payment_Transaction  $authorizationTransaction
     */
    protected function _billpayCapture(
        Varien_Object $payment,
        $amount,
        Mage_Sales_Model_Order_Payment_Transaction $authorizationTransaction = null
    )
    {
        //Check if we have a valid authorization
        $hasAvailableAuthorization = $this->_canCaptureAuthorization($payment, $authorizationTransaction);
        if (!$hasAvailableAuthorization) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('capture')
                ->processMessage('There is no open authorization to capture.');
        }

        //Get the authorization
        if (null === $authorizationTransaction) {
            $authorizationTransaction = $payment->getAuthorizationTransaction();
        }

        //Get the request model
        $request = $this->_createRequestModel('capture', $payment);

        //Set the authorization transaction model and the invoice amount
        $request
            ->setReferencedTransactionModel($authorizationTransaction)
            ->setAmount($amount);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );

            //Don't close the capture transaction we are available to refund
            $payment->setIsTransactionClosed(false);

            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_capture_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('capture')
                ->processErrorCode($request, $this->getCode());
        }
    }

    /**
     * Extends the parent validations rules with bank data check rules.
     *
     * @param   array   $validationRules
     * @return  array
     */
    protected function _getValidationRules(array $validationRules = array())
    {
        //Collect the parent rules first
        $validationRules = parent::_getValidationRules($validationRules);

        //Validate owner
        $validationRules[self::KEY_EFT_OWNER] = array(
            'NotEmpty',
            array('StringLength', array('min' => 1, 'max' => 50)),
            Zend_Filter_Input::MESSAGES => array(
                $this->_getHelper()->__("Your account holder is invalid."),
                $this->_getHelper()->__(
                    "The maximum length for the field %s are %s %s.",
                    $this->_getHelper()->__("Account holder"),
                    "%max%",
                    $this->_getHelper()->__("Characters")
                ),
            )
        );

        //Validate the ban
        $validationRules[self::KEY_EFT_BAN_ENC] = array(
            'Digits',
            array('StringLength', array('min' => 1, 'max' => 10)),
            Zend_Filter_Input::MESSAGES => array(
                $this->_getHelper()->__("Your bank account number is invalid."),
                $this->_getHelper()->__(
                    "The maximum length for the field %s are %s %s.",
                    $this->_getHelper()->__("Bank account number"),
                    "%max%",
                    $this->_getHelper()->__("Digits")
                ),
            )
        );

        //Validate the bcn
        $validationRules[self::KEY_EFT_BCN] = array(
            'Digits',
            array('StringLength', array('min' => 1, 'max' => 8)),
            Zend_Filter_Input::MESSAGES => array(
                $this->_getHelper()->__("Your bank code number is invalid."),
                $this->_getHelper()->__(
                    "The maximum length for the field %s are %s %s.",
                    $this->_getHelper()->__("Bank code number"),
                    "%max%",
                    $this->_getHelper()->__("Digits")
                ),
            )
        );

        //The term validation can only skip if the current model is a quote
        if ($this->getOracle()->isOrder() || !self::isIgnoreTermValidationFlagActive()) {
            //Check if we have rates
            if (!$this->getBillpaySession()->hasRates()) {
                $this->_getHelper()->getPaymentHelper()
                    ->getPaymentValidationErrorHandler($this->getFieldName(self::KEY_TERM))
                    ->processMessage('Please recalculate your rates and select a new one.');
            }

            //Validate the term if selected
            $validationRules[self::KEY_TERM] = array(
                'Digits',
                array('InArray', array('haystack' => array_keys($this->getBillpaySession()->getRates()))),
                Zend_Filter_Input::ALLOW_EMPTY  => false,
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                Zend_Filter_Input::MESSAGES     => array(
                    $this->_getHelper()->__("Your selected term is invalid."),
                    $this->_getHelper()->__("Your selected term is invalid."),
                ),
            );
        }

        return $validationRules;
    }

    /**
     * Return the payment plan as array.
     *
     * @return  array
     */
    public function getPaymentPlan()
    {
        if (null === $this->_cacheGetPaymentPlan) {
            $plan = $this->getInfoInstance()->getData(self::KEY_BILLPAY_PAYMENT_PLAN);
            if ($plan) {
                $plan = @unserialize($plan);
            }

            //We always return an array
            if (!is_array($plan)) {
                $plan = array();
            }

            $this->_cacheGetPaymentPlan = $plan;
        }

        return $this->_cacheGetPaymentPlan;
    }

    /**
     * Set a payment plan to the payment instance.
     *
     * @param   string|array    $paymentPlan
     * @return  Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay
     */
    public function setPaymentPlan($paymentPlan)
    {
        //We can only save strings
        if (is_array($paymentPlan)) {
            $paymentPlan = serialize($paymentPlan);
        }

        //Type check
        if (!is_string($paymentPlan)) {
            throw new Exception("Wrong type is given to store the payment plan.");
        }

        //Store the payment plan and reset the cache
        $this->getInfoInstance()->setData(self::KEY_BILLPAY_PAYMENT_PLAN, $paymentPlan);
        $this->resetPaymentPlanCache();

        return $this;
    }

    /**
     * Reset the cached payment plan array.
     *
     * @return  Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay
     */
    public function resetPaymentPlanCache()
    {
        $this->_cacheGetPaymentPlan = null;
        return $this;
    }

    /**
     * Return true if we need a rates request.
     *
     * @return  boolean
     */
    public function getIsRatesRequestNeeded()
    {
        $billpaySession = $this->getBillpaySession();
        return !$billpaySession->sameHash($this->getOracle()->getUniquePaymentHash())
            || !$billpaySession->hasRates();
    }

    /**
     * Set a flag that indicates if the term validation should skipped.
     *
     * @param   boolean $ignore
     */
    public static function setIgnoreTermValidationFlag($ignore)
    {
        self::$_ignoreTermValidationFlag = (boolean) $ignore;
    }

    /**
     * Get a flag that indicates if the term validation should skipped.
     */
    public static function isIgnoreTermValidationFlagActive()
    {
        return self::$_ignoreTermValidationFlag;
    }
}