<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Billpay_Ratepay
    extends Dotsource_Paymentoperator_Block_Info_Billpay_Abstract
{

    /**
     * Add a new block to show the payment plan informations.
     */
    protected function _toHtml()
    {
        //Check if we are able to show the payment plan
        if ($this->showPaymentPlan()) {
            //Build the block
            $block = Mage::app()->getLayout()->createBlock("paymentoperator/info_billpay_ratepay_rateoverview")
                ->setOrder($this->getInfo()->getMethodInstance()->getOracle()->getModel())
                ->setPaymentPlan($this->getPaymentPlan())
                ->setConditions($this->getConditions());

            //Set the block as child
            $this->setChild('paymentoperator_billpay_ratepay_payment_plan', $block);
        }

        return parent::_toHtml();
    }

    /**
     * Return the payment informations.
     *
     * @param   Varien_Object|null   $transport
     * @return  Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        //Return the already cached payment information if available
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        /* @var $method Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay */
        $transport      = parent::_prepareSpecificInformation($transport);
        $method         = $this->getInfo()->getMethodInstance();
        $paymentHelper  = Mage::helper('payment');

        //Get the selected term
        $term = $method->getTerm();
        if (!$term) {
            $term = '-';
        }

        //Show the customer bank information
        $transport[$paymentHelper->__('Account holder')]        = $method->getEftOwner();
        $transport[$paymentHelper->__('Bank account number')]   = sprintf('xxxx-%s', $method->getEftBan3());
        $transport[$paymentHelper->__('Bank code number')]      = $method->getEftBcn();

        //Show the payment plan
        if ($this->showPaymentPlan()) {
            //Show the selected term
            $transport[$this->__('Selected Term')] = $term;

            $conditions = $this->getConditions();
            $transport[$paymentHelper->__('Surcharge')]
                =  $this->formatRatePrice($conditions['surcharge'], false);
            $transport[$paymentHelper->__('Fee')]
                =  $this->formatRatePrice($conditions['fee'], false);
            $transport[$paymentHelper->__('Base Billpay Grand Total')]
                = $this->formatRatePrice($conditions['base_billpay_grand_total'], false);

            $transport[$paymentHelper->__('Rate plan')] = "  ";
        }

        return $transport;
    }

    /**
     * Return true if the payment plan should show.
     *
     * @return  boolean
     */
    public function showPaymentPlan()
    {
        /* @var $infoInstance Dotsource_Paymentoperator_Model_Rewrite_Sales_Order_Payment */
        $infoInstance = $this->getInfo();
        return $infoInstance->getMethodInstance()->getOracle()->isOrder()
            && (!$infoInstance->isLastCaptureTransactionClosed() || $this->isAdminOrInvoiceContext());
    }

    /**
     * Return the customers payment plan.
     *
     * @return  array
     */
    public function getPaymentPlan()
    {
        $paymentPlan = $this->getInfo()->getMethodInstance()->getPaymentPlan();
        return $paymentPlan['payment_plan'];
    }

    /**
     * Return the customers conditions.
     *
     * @return  array
     */
    public function getConditions()
    {
        $paymentPlan = $this->getInfo()->getMethodInstance()->getPaymentPlan();
        return $paymentPlan['conditions'];
    }

    /**
     * Format the price with the currency.
     *
     * @param   string  $price
     * @return  string
     */
    public function formatRatePrice($price)
    {
        return $this->getInfo()->getMethodInstance()->getOracle()->getModel()
            ->getBaseCurrency()->formatPrecision($price, 2, array(), false);
    }
}