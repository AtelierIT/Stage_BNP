<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * sklein - initial contents
 */
class Dotsource_Paymentoperator_Block_Email_Billpay_Order_Ratepay
    extends Mage_Sales_Block_Order_Totals
{
    /**
     * Overwrite order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        /**
         * if payment method is PaymentOperator Billpay Payment By Installments add these infos
         * to total array
         */
        $source = parent::getSource();
        $payment = $source->getPayment();
        $paymentMethod = $payment->getMethod();
        if ($paymentMethod === Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::CODE) {
            $paymentPlan = $payment->getMethodInstance()->getPaymentPlan();
            $helper = Mage::helper('paymentoperator');
            $paymentConditions = $paymentPlan['conditions'];
            $this->_totals['billpay_surcharge'] = new Varien_Object(array(
                'code'  => 'billpay_surcharge',
                'field' => 'billpay_surcharge',
                'value' => $paymentConditions['surcharge'],
                'label' => $this->__('Surcharge')
            ));
            $this->_totals['billpay_fee'] = new Varien_Object(array(
                'code'  => 'billpay_fee',
                'field' => 'billpay_fee',
                'value' => $paymentConditions['fee'],
                'label' => $this->__('Fee')
            ));
            $this->_totals['base_billpay_grand_total'] = new Varien_Object(array(
                'code'  => 'base_billpay_grand_total',
                'field' => 'base_billpay_grand_total',
                'strong'=> true,
                'value' => $paymentConditions['base_billpay_grand_total'],
                'label' => $this->__('Base Billpay Grand Total')
            ));
            $this->_totals['grand_total']->setData('strong', false);
        }
        return $this;
    }
}