<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_BillpayController
    extends Mage_Core_Controller_Front_Action
{

    /**
     * Get the rates for billpay ratepay.
     */
    public function ratesAction()
    {
        //Check for the right payment
        $paymentData    = $this->getRequest()->getPost('payment', array());
        $result         = array();

        try {
            //Check the payment method first
            if (empty($paymentData['method'])
                || Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::CODE !== $paymentData['method']
            ) {
                Mage::throwException($this->__("A wrong payment method was given. Please try again or choose another payment method."));
            }

            //At this point we need to skip the term validation
            Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::setIgnoreTermValidationFlag(true);

            //Validate and save the payment informations first
            $quote = $this->_getHelper()->getQuote();
            $this->_savePaymentAction($quote, $paymentData);

            //Set the rates as result
            $rates      = $quote->getPayment()->getMethodInstance()->getBillpaySession()->getRates();
            $ratesBlock = Mage::app()->getLayout()->createBlock(
                'Mage_Core_Block_Template',
                'paymentoperator/form_billpay_rates',
                array('template' => 'paymentoperator/form/billpay/rates.phtml')
            );

            //Write information for block, get the html for the opc payment form
            $ratesHtml = array();
            foreach ($rates as  $term => $rate) {
                $ratesBlock->setCondition($rate['conditions']);
                $ratesBlock->setPaymentPlan($rate['payment_plan']);
                $ratesBlock->setTerm($term);
                $ratesBlock->setPaymentMethod($quote->getPayment()->getMethodInstance());
                $result[$term]['html'] = $ratesBlock->toHtml();
            }
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        //Set the current result as json response
        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode($result)
        );
    }

    /**
     * Set and save the given payment information.
     *
     * @param   Mage_Sales_Model_Quote  $quote
     * @param   array                   $paymentInformation
     */
    public function _savePaymentAction(Mage_Sales_Model_Quote $quote, array $paymentInformation)
    {
        //Get the selected method
        $paymentMethod = isset($paymentInformation['method']) ? $paymentInformation['method'] : null;

        //Set the payment method
        if ($quote->isVirtual()) {
            $quote->getBillingAddress()->setPaymentMethod($paymentMethod);
        } else {
            $quote->getShippingAddress()->setPaymentMethod($paymentMethod);
        }

        //Shipping totals may be affected by payment method
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }
        $quote->getPayment()->importData($paymentInformation);
        $quote->save();
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