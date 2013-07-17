<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Observer_Risk
{

    /** Holds a risk per quote */
    protected $_riskPerQuote = array();


    /**
     * Do the risk check on the order place event.
     *
     * @param Varien_Event_Observer $observer
     */
    public function doRiskCheck(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getOrder();

        /* @var $risk Dotsource_Paymentoperator_Model_Check_Risk_Risk */
        $risk = Mage::getModel('paymentoperator/check_risk_risk')->init($order);

        //Check if the risk model is available
        if ($risk->isAvailable()) {
            //Only send a request logic and error handling if we can
            if ($risk->isAllowToSendRequest()) {
                //Process a real risk check if need
                $risk->process();

                //Get the response for error checking
                $response = $risk->getResponse();

                //If we have an error in the response we only allow fallback payments if it active
                //and we re show the terms and conditions if it needs
                if (!$response || $response->hasError()) {
                    $risk->setUseFallbackFlag(true);
                }
            }

            $result = $risk->isPaymentAvailable($order->getPayment()->getMethodInstance());

            //Check if the payment is available
            if (false === $result) {
                //Redirect to the payment
                if ($this->_getHelper()->isFrontend()) {
                    $this->_getHelper()->getFrontendCheckoutSession()
                        ->setGotoSection('payment')
                        ->setUpdateSection('payment-method');
                }

                //Throw the exception to quit the logic
                Mage::throwException(
                    $this->_getHelper()->__(
                        'Your current payment is not available. '
                        . 'Please choose another payment method.'
                    )
                );
            }
        }
    }


    /**
     * Do a dry risk check.
     *
     * @param $observer
     */
    public function doDryRiskCheck(Varien_Event_Observer $observer)
    {
        /* @var $quote Mage_Sales_Model_Quote */
        /* @var $quote Mage_Payment_Model_Method_Abstract */
        $result         = $observer->getResult();
        $quote          = $observer->getQuote();
        $methodInstance = $observer->getMethodInstance();

        //Quote available or the payment is default disabled
        if (!$quote || !$quote->getId() || !$result->isAvailable) {
            return;
        }

        //Get the risk model
        $risk = $this->_getRiskModel($quote);

        //Check if we have a previous risk response
        if ($risk->isAvailable()) {
            //Check if the payment is available
            $available = $risk->isPaymentAvailable($methodInstance);

            //If the value is a boolean we can use this as result of the observer
            if (is_bool($available)) {
                $result->isAvailable = $available;
            }
        }
    }


    /**
     * Sync the content from the session to the customer if is necessary.
     *
     * @param $observer
     */
    public function syncRiskResponse(Varien_Event_Observer $observer)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getOrder();

        //If we don't have a customer id the
        if (!$order->getCustomerId()) {
            return;
        }

        /* @var $risk Dotsource_Paymentoperator_Model_Check_Risk_Risk */
        $risk = Mage::getModel('paymentoperator/check_risk_risk')->init($order);

        //Sync the content to the primary object
        if ($risk->isAvailable() && $risk->getStorageModel()->hasPrimaryObject()) {
            //Save the primary object after the sync
            $risk->sync(true);
        }
    }


    /**
     * Return the specific risk model from the model.
     *
     * @param $model
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Risk
     */
    protected function _getRiskModel($model)
    {
        //Get the key for the risk model
        $key = $this->_getKey($model);

        //Create a risk model to the key if not exists
        if (!isset($this->_riskPerQuote[$key])) {
            $this->_riskPerQuote[$key] = Mage::getModel('paymentoperator/check_risk_risk')->init($model);
        }

        //Return the risk model
        return $this->_riskPerQuote[$key];
    }


    /**
     * Return a unique key for the risk model.
     *
     * @param mixed $model
     * @return string
     */
    protected function _getKey($model)
    {
        //Holds the model type
        $type = null;

        if ($model instanceof Mage_Sales_Model_Quote) {
            $type = 'quote';
        } elseif ($model instanceof Mage_Sales_Model_Order) {
            $type = 'order';
        } else {
            Mage::throwException('Only quotes or quotes are supported.');
        }

        return "risk_check_{$type}_id_{$model->getId()}";
    }


    /**
     * Clear the risk response from the session object.
     *
     * @param $observer
     */
    public function clearRiskSession(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('paymentoperator/session_risk')->clear();
    }


    /**
     * Clear the risk model quote cache.
     *
     * @param $observer
     */
    public function clearRiskModelCache(Varien_Event_Observer $observer)
    {
        $this->_riskPerQuote = array();
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