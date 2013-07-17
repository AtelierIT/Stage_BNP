<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * sklein - initial contents
 */
class Dotsource_Paymentoperator_Model_Total_Pdf
    extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    /**
     * get amount of billpay ratepay additional payment data for pdf invoice
     *
     * @return float
     */
    public function getAmount()
    {
        $paymentPlan = $this->getOrder()->getPayment()->getMethodInstance()->getPaymentPlan();

        return $paymentPlan['conditions'][$this->getSourceField()] ? $paymentPlan['conditions'][$this->getSourceField()] : 0;
    }
}