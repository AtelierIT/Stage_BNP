<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_CheckoutController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * This action will called from the checkout step. To do
     * a real risk check of the customer if needed and return the filtered
     * payment methods.
     */
    public function riskAction()
    {
        //Get the needed agreements
        $requiredAgreements = $this->_getRequiredAgreementIds();

        //Check for the agreement ids
        if ($requiredAgreements) {
            //Get the agreements from the request
            $postedAgreements   = array_keys($this->getRequest()->getPost('agreement', array()));
            $missingIds         = array_diff($requiredAgreements, $postedAgreements);

            //If the array is not empty some agreement ids are missing
            if ($missingIds) {
                $result['success']  = false;
                $result['error']    = true;
                $result['message']  = $this->_getHelper()->__('Please agree to all the terms and conditions.');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }
        }

        //At this point we send a real request to paymentoperator if needed
        /* @var $risk Dotsource_Paymentoperator_Model_Check_Risk_Risk */
        $risk = Mage::getModel('paymentoperator/check_risk_risk')
            ->init($this->_getHelper()->getQuote())
            ->process();

        //Get the response
        $response = $risk->getResponse();

        //If we have an error in the response we only allow fallback payments if it active
        if (!$response || $response->hasError()) {
            $risk->setUseFallbackFlag(true);
        }

        //Create the payment methods block with the new filtered payment methods
        $this->loadLayout('checkout_onepage_paymentmethod');

        //After a process check we don't need to show the terms and conditions
        $this->getLayout()->getBlock('root')->setForceShowTermsAndConditionsValue(false);

        $this->renderLayout();

        //Holds the return data
        $result = array();
        $result['goto_section'] = 'payment';
        $result['update_section'] = array(
            'name' => 'payment-method',
            'html' => $this->getLayout()->getOutput()
        );

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }


    /**
     * Return the required agreements.
     *
     * @return array
     */
    protected function _getRequiredAgreementIds()
    {
        return Mage::helper('checkout')->getRequiredAgreementIds();
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