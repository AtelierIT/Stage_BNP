<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Refund
    extends Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract
{

    /**
     * Don't encode these vales from the request.
     * @var array
     */
    protected $_useUndecodeParameters   = array(
        'bpconditionslist',
        'bppaymentplan',
    );

    /**
     * Holds the cached result from the method call getRate().
     * @var array|null
     */
    protected $_cachesRateArray         = null;


    /**
     * Return the new customer rate if a new rate is given. If no new rate is given
     * this method return an empty array.
     *
     * @return  array
     */
    public function getRate()
    {
        if (null === $this->_cachesRateArray) {
            //Get the values as string
            $conditionsList = $this->_getBillpayConditions();
            $paymentPlan    = $this->_getBillpayPaymentPlan();
            $currencyCode   = $this->getRequest()->getOracle()->getBaseCurrencyCode();
            $rate           = array();

            //check if we able to parse the new rate
            if ($paymentPlan && $conditionsList) {
                //Parse the data as array
                $conditionsList = $this->_getBillpayHelper()->parseConditionsList(
                    $conditionsList,
                    $currencyCode
                );
                $paymentPlan = $this->_getBillpayHelper()->parsePaymentPlan(
                    $paymentPlan,
                    $currencyCode
                );

                //Join the two arrays
                $rate = array(
                    'conditions'    => $conditionsList,
                    'payment_plan'  => $paymentPlan,
                );
            }

            //Set the cache
            $this->_cachesRateArray = $rate;
        }

        return $this->_cachesRateArray;
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
}