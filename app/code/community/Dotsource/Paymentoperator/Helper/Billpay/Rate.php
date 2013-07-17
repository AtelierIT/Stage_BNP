<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Billpay_Rate
{

    /**
     * Holds a map of key for the combine process.
     * @var array
     */
    protected static $_conditionsCombineMap = array(
        'term',
        'base_sub_total',
        'base_grand_total',
        'surcharge',
        'base_billpay_grand_total',
        'monthly_interest_rate',
        'annual',
        'fee',
    );

    /**
     * Holds all keys that needs a price reformat
     * @var array
     */
    protected static $_conditionsPriceFormatKeys = array(
        'base_sub_total',
        'base_grand_total',
        'surcharge',
        'base_billpay_grand_total',
        'fee',
    );


    /**
     * Parse the given conditions list as array.
     *
     * @param   string  $conditionsList
     * @param   string  $currencyCode
     * @return  array
     */
    public function parseAuthorizationConditionsList($conditionsList, $currencyCode)
    {
        //Holds the key for the parsed values
        static $conditionsListCombineMap = array(
            'term',
            'base_sub_total',
            'base_grand_total',
            'surcharge',
            'base_billpay_grand_total',
            'monthly_interest_rate',
            'annual',
            'fee',
        );

        //Holds a list of keys that we use for price reformat
        static $conditionsListPriceKeys = array(
            'base_sub_total',
            'base_grand_total',
            'surcharge',
            'base_billpay_grand_total',
            'fee',
        );

        //Stuff we need
        $converter  = $this->_getHelper()->getConverter();
        $rates      = array();

        //Parse process
        foreach (explode('+', $conditionsList) as $conditions) {
            $conditionParts = explode(';', $conditions);
            $conditionParts = array_combine($conditionsListCombineMap, $conditionParts);
            $term           = $conditionParts['term'];

            //Format all the prices
            foreach ($conditionsListPriceKeys as $priceKeys) {
                $conditionParts[$priceKeys] = $converter->revertFormatPrice($conditionParts[$priceKeys], $currencyCode);
            }

            //Format in percent
            $conditionParts['monthly_interest_rate']
                = $converter->revertFormatPrice($conditionParts['monthly_interest_rate'], null);
            $conditionParts['annual']
                = $converter->revertFormatPrice($conditionParts['annual'], null);

            //Add the conditions to the result rates
            $rates[$term]['conditions'] = $conditionParts;
        }

        return $rates;
    }

    /**
     * Parse the given conditions list as array.
     *
     * @param   string  $conditionsList
     * @param   string  $currencyCode
     * @return  array
     */
    public function parseConditionsList($conditionsList, $currencyCode)
    {
        $result = $this->parseAuthorizationConditionsList($conditionsList, $currencyCode);

        //Only one term are parsed
        $result = array_pop($result);
        return $result['conditions'];
    }

    /**
     * Parse the given payment plan as array.
     *
     * @param   string  $paymentPlan
     * @param   string  $currencyCode
     * @return  array
     */
    public function parseAuthorizationPaymentPlan($paymentPlan, $currencyCode)
    {
        $converter  = $this->_getHelper()->getConverter();
        $rates      = array();

        //Process the payment plans
        foreach (explode('+', $paymentPlan) as $paymentPlan) {
            $paymentPlanParts   = explode(';', $paymentPlan);
            $term               = array_shift($paymentPlanParts);
            $termNumber         = 0;
            $ratePlan           = array();

            //Parse the data in the default format
            foreach ($paymentPlanParts as $amount) {
                $ratePlan[] = array(
                    'rate_number'   => ++$termNumber,
                    'rate_amount'   => $converter->revertFormatPrice($amount, $currencyCode),
                    'due_date'      => null,
                );
            }

            //Add the payment plan
            $rates[$term]['payment_plan'] = $ratePlan;
        }

        return $rates;
    }

    /**
     * Parse the given payment plan as array.
     *
     * @param   string  $paymentPlan
     * @param   string  $currencyCode
     * @return  array
     */
    public function parsePaymentPlan($paymentPlan, $currencyCode)
    {
        $converter  = $this->_getHelper()->getConverter();
        $plan       = array();

        //Format the fields
        foreach (explode('+', $paymentPlan) as $rate) {
            $planParts = explode(';', $rate);

            //Use the right map for parse the result
            if (2 === count($planParts)) {
                $planParts = array_combine(
                    array(
                        'rate_number',
                        'rate_amount',
                    ),
                    $planParts
                );
                $planParts['due_date'] = null;
            } elseif (3 === count($planParts)) {
                $planParts = array_combine(
                    array(
                        'rate_number',
                        'rate_amount',
                        'due_date'
                    ),
                    $planParts
                );
            } else {
                throw new Exception("The data for parsing are given.");
            }
            $planParts['rate_amount']   = $converter->revertFormatPrice($planParts['rate_amount'], $currencyCode);
            $plan[]                     = $planParts;
        }

        return $plan;
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