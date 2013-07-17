<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Capture
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
     * Cache the getPaymentPlan() method call.
     * @var array|null
     */
    protected $_cachesPaymentPlanArray  = null;


    /**
     * Return the payment plan as array.
     *
     * @return  array
     */
    public function getPaymentPlan()
    {
        if (null === $this->_cachesPaymentPlanArray) {
            $currencyCode = $this->getRequest()->getOracle()->getBaseCurrencyCode();

            //Parse and cache the result
            $this->_cachesPaymentPlanArray = $this->_getBillpayHelper()->parsePaymentPlan(
                $this->_getBillpayPaymentPlan(),
                $currencyCode
            );
        }

        return $this->_cachesPaymentPlanArray;
    }

    /**
     * Return the payment plan as string.
     *
     * @return  string|null
     */
    protected function _getBillpayPaymentPlan()
    {
        return $this->getResponse()->getData('bppaymentplan');
    }

    /**
     * Return true if the request was not successful.
     *
     * @return  boolean
     */
    public function hasError()
    {
        return parent::hasError()
            || !$this->_getBillpayPaymentPlan();
    }
}