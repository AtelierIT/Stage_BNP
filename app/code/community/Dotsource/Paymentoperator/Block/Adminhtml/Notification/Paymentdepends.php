<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Adminhtml_Notification_Paymentdepends
    extends Mage_Adminhtml_Block_Template
{

    /**
     * Return all depends messages from the payment methods.
     *
     * @return  array
     */
    public function getDependsMessages()
    {
        /* @var $paymentHelper Mage_Payment_Helper_Data */
        $paymentHelper  = Mage::helper('payment');
        $messages       = array();
        $paymentMethods = array();

        //Get the payment methods
        if (is_callable($paymentHelper, 'getPaymentMethods')) {
            $paymentMethods = $paymentHelper->getPaymentMethods();
        } else {
            $paymentMethods = Mage::getStoreConfig(Mage_Payment_Helper_Data::XML_PATH_PAYMENT_METHODS);
        }

        foreach ($paymentMethods as $paymentCode => $data) {
            //Only check active paymentoperator payment methods
            $paymentMethod = $paymentHelper->getMethodInstance($paymentCode);
            if (!$paymentMethod instanceof Dotsource_Paymentoperator_Model_Payment_Abstract
                || !$paymentMethod->getConfigData('active')
            ) {
                continue;
            }

            //Just add non empty depends
            $depends = $paymentMethod->getDepends();
            if ($depends) {
                $messages[$paymentMethod->getTitle()] = $depends;
            }
        }

        return $messages;
    }
}