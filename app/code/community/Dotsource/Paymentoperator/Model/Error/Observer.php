<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Observer
{

    /**
     * Destroys the quote and redirect to the base url.
     *
     * @param Dotsource_Paymentoperator_Model_Error_Manager $manager
     */
    public function destroyQuote(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        $this->_getHelper()->getFrontendCheckoutSession()->clear();
    }

    /**
     * Update the exception handler for redirect to the checkout payment.
     *
     * @param Dotsource_Paymentoperator_Model_Error_Manager $manager
     */
    public function checkoutPaymentRedirect(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        //Redirect to payment method
        $this->_getHelper()->getFrontendCheckoutSession()
            ->setGotoSection('payment')
            ->setUpdateSection('payment-method');
    }

    /**
     * Clears the current quote payment.
     *
     * @param Dotsource_Paymentoperator_Model_Error_Manager $manager
     */
    public function clearQuotePayment(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        //Get the current payment
        $quote      = $this->_getHelper()->getFrontendQuote();
        $payment    = $quote->getPayment();

        //Check if we need to reset the payment
        if ($payment instanceof Mage_Sales_Model_Quote_Payment
            && $payment->hasData($payment->getIdFieldName())
        ) {
            //Remove the payment
            $quote
                ->removePayment()
                ->save();
        }
    }

    /**
     * Disable the current used payment method for the current checkout
     * session.
     *
     * @param Dotsource_Paymentoperator_Model_Error_Manager $manager
     */
    public function disablePaymentMethod(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        //Get the current payment
        $session    = $this->_getHelper()->getFrontendCheckoutSession();
        $quote      = $session->getQuote();
        $payment    = $quote->getPayment();

        //Check if we need to reset the payment
        if ($payment instanceof Mage_Sales_Model_Quote_Payment
            && $payment->hasData($payment->getIdFieldName())
        ) {
            //Disable the current used payment method
            $this->_getHelper()->getPaymentHelper()->disablePaymentForCheckoutSession(
                $payment->getMethod()
            );
        }

        //Also clear the payment
        $this->clearQuotePayment($manager);
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