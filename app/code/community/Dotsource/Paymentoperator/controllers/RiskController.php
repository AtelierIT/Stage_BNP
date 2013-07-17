<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_RiskController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * This request url will clean all risk data from the session and customer.
     */
    public function cleanAction()
    {
        //Only in demo mode
        if (!$this->_getHelper()->isDemoMode()) {
            $this->_redirect('noroute');
            return;
        }

        //Clean sessions
        Mage::getSingleton('paymentoperator/session_risk')->clear();

        //Clean from customer object
        $oracle = new Dotsource_Paymentoperator_Model_Oracle_Type_Order();
        $oracle->setModel($this->_getHelper()->getFrontendQuote());
        $customer = $oracle->getCustomer();

        //If we have a valid customer object clear the risk data
        if ($customer && $customer->getId()) {
            $customer
                ->setData('paymentoperator_risk_check', null)
                ->save();
        }

        $this->_redirect('checkout/onepage');
    }


    /**
     * Show the risk data from the current customer or risk session.
     */
    public function showAction()
    {
        //Only in demo mode
        if (!$this->_getHelper()->isDemoMode()) {
            $this->_redirect('noroute');
            return;
        }

        $risk = new Dotsource_Paymentoperator_Model_Check_Risk_Risk();
        $risk->init($this->_getHelper()->getFrontendQuote());
        $response = $risk->getResponse();

        echo '<pre>';
        if (is_object($response)) {
            $response = $response->getResponse()->getData();
        }
        var_dump($response);
        echo '</pre>';
        die();
    }


    /**
     * Show the risk data from the current customer or risk session.
     */
    public function setAction()
    {
        //Only in demo mode
        if (!$this->_getHelper()->isDemoMode()) {
            $this->_redirect('noroute');
            return;
        }

        $newAktion = $this->getRequest()->get('aktion');

        if (empty($newAktion)) {
            die("Parameter \"aktion\" can't be empty.");
        }

        $risk = new Dotsource_Paymentoperator_Model_Check_Risk_Risk();
        $risk->init($this->_getHelper()->getFrontendQuote());
        $response = $risk->getResponse();

        //Valid data?
        if (is_object($response)) {
            $response = $response->getResponse()->getData();
            $response['aktion'] = $newAktion;
            $risk->getStorageModel()
                ->setData($risk->getStorageKey(), $response)
                ->sync($risk->getStorageKey(), true);
        } else {
            die("No valid response available.");
        }
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