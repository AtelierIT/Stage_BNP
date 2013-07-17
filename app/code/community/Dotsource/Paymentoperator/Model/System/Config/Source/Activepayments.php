<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Activepayments
{

    /**
     * Return a list of all active payments.
     *
     * @param boolean $noneElement
     * @return array
     */
    public function toOptionArray()
    {
        //Payment methods
        $payments       = array();

        //Get all payment methods
        $paymentMethods = Mage::helper('payment')->getStoreMethods(
            0,
            null
        );

        //Build the info array
        if (!empty($paymentMethods)) {
            foreach ($paymentMethods as $payment) {
                $payments[] = array(
                    'value' => $payment->getCode(),
                    'label' => $payment->getTitle(),
                );
            }
        }

        return $payments;
    }
}