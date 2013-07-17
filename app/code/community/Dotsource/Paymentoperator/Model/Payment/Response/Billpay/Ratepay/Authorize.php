<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract
{

    /**
     * Don't encode these vales from the request.
     * @var array
     */
    protected $_useUndecodeParameters       = array(
        'bpconditionslist',
        'bppaymentplan',
    );

    /**
     * Cache the getRates() method call.
     * @var array|null
     */
    protected $_cachesRatesArray            = null;


    /**
     * Returns the rates from the response model.
     *
     * @return  array
     */
    public function getRates()
    {
        if (null === $this->_cachesRatesArray) {
            //Get the values as string
            $conditionsList = $this->_getBillpayConditions();
            $paymentPlans   = $this->_getBillpayPaymentPlan();
            $currencyCode   = $this->getRequest()->getOracle()->getBaseCurrencyCode();

            //Parse the data as array
            $conditionsList = $this->_getBillpayHelper()
                ->parseAuthorizationConditionsList($conditionsList, $currencyCode);
            $paymentPlans   = $this->_getBillpayHelper()
                ->parseAuthorizationPaymentPlan($paymentPlans, $currencyCode);

            //Merge the two informations in one array
            $rates = array();
            $terms = array_intersect(array_keys($conditionsList), array_keys($paymentPlans)) ;
            foreach ($terms as $term) {
                $rates[$term] = array(
                    'conditions'    => $conditionsList[$term]['conditions'],
                    'payment_plan'  => $paymentPlans[$term]['payment_plan'],
                );
            }

            //Set the cache
            $this->_cachesRatesArray = $rates;
        }

        return $this->_cachesRatesArray;
    }

    /**
     * Return the conditions.
     *
     * @return  string|null
     */
    protected function _getBillpayConditions()
    {
        return $this->getResponse()->getData('bpconditionslist');
    }

    /**
     * Return the payment plan.
     *
     * @return  string|null
     */
    protected function _getBillpayPaymentPlan()
    {
        return $this->getResponse()->getData('bppaymentplan');
    }

    /**
     * Return the link to the billpay terms and conditions.
     *
     * @return  string|null
     */
    public function getBillpayTermsAndConditionsLink()
    {
        return $this->getResponse()->getData('bplink1');
    }

    /**
     * Return the link to the billpay privacy policy.
     *
     * @return  string|null
     */
    public function getBillpayPrivacyPolicyLink()
    {
        return $this->getResponse()->getData('bplink2');
    }

    /**
     * Return the link to the billpay terms of payment.
     *
     * @return  string|null
     */
    public function getBillpayTermsOfPaymentLink()
    {
        return $this->getResponse()->getData('bplink3');
    }

    /**
     * Return true if the request was not successful.
     *
     * @return  boolean
     */
    public function hasError()
    {
        return parent::hasError()
            || !$this->_getBillpayConditions()
            || !$this->_getBillpayPaymentPlan();
    }
}