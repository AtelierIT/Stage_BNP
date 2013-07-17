<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Observer_Billpay_Ratepay
{

    /**
     * Update the payment plan if its possible.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function syncNewRateAfterRefunding(Varien_Event_Observer $observer)
    {
        /* @var $method     Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay */
        /* @var $request    Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Ratepay_Refund */
        /* @var $response   Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Refund */
        $method     = $observer->getMethod();
        $request    = $observer->getRequest();
        $response   = $observer->getResponse();
        $newRate    = $response->getRate();

        //Set the new payment plan
        if ($newRate) {
            $method->setPaymentPlan($newRate);
        }
    }

    /**
     * Sync finished payment plan to the payment.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function syncPaymentPlan(Varien_Event_Observer $observer)
    {
        /* @var $method     Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay */
        /* @var $request    Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Ratepay_Capture */
        /* @var $response   Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Capture */
        $method     = $observer->getMethod();
        $request    = $observer->getRequest();
        $response   = $observer->getResponse();

        //Merge the new payment plan with old one
        $responsePaymentPlan                = $response->getPaymentPlan();
        $paymentPaymentPlan                 = $method->getPaymentPlan();
        $paymentPaymentPlan['payment_plan'] = $responsePaymentPlan;

        //Set the new payment plan back to the payment
        $method->setPaymentPlan($paymentPaymentPlan);
    }
}