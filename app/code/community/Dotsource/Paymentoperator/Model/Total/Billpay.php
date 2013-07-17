<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * sklein - initial contents
 */
class Dotsource_Paymentoperator_Model_Total_Billpay
    extends Mage_Sales_Model_Quote_Address_Total_Abstract
{

    /**
     * Add the billpay specific total informations.
     *
     * @param   Mage_Sales_Model_Quote_Address          $address
     * @return  Dotsource_Paymentoperator_Model_Total_Billpay
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        /* @var $paymentMethod Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay */
        $payment            = $address->getQuote()->getPayment();
        $paymentMethod      = $payment->getMethod();

        //Valid billpay payment method?
        if ($paymentMethod
            && $paymentMethod === Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::CODE
            && $address->getAddressType() === Mage_Sales_Model_Quote_Address::TYPE_BILLING
            && $payment->getMethodInstance()->getPaymentPlan()
        ) {
            $paymentPlan        = $payment->getMethodInstance()->getPaymentPlan();
            $paymentConditions  = $paymentPlan['conditions'];
            $helper             = Mage::helper('paymentoperator');

            $address->addTotal(array(
                'code' => 'billpay_ratepay_fee',
                'title' => $helper->__('Fee'),
                'value' => $paymentConditions['fee'],
                'area' => 'footer'
            ));
            $address->addTotal(array(
                'code' => 'billpay_ratepay_surcharge',
                'title' => $helper->__('Surcharge'),
                'value' => $paymentConditions['surcharge'],
                'area' => 'footer'
            ));
             $address->addTotal(array(
                'code' => 'billpay_ratepay_total',
                'title' => $helper->__('Base Billpay Grand Total'),
                'area' => 'footer',
                'value' => $paymentConditions['base_billpay_grand_total']
            ));

        }
    }
}