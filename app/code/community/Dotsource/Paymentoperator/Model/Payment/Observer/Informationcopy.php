<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Observer_Informationcopy
{

    /**
     * Return an array with payment and method instance if payment informations
     * are backupable.
     *
     * @param   Varien_Event_Observer   $observer
     * @param   string                  $variable
     * @return  array|null
     */
    protected function _getMethodInstance(Varien_Event_Observer $observer, $variable = 'payment')
    {
        /* @var $payment Mage_Payment_Model_Info */
        $payment = $observer->getEvent()->getData($variable);
        if (!$payment || !$payment->getMethod()) {
            return null;
        }

        /* @var $methodInstance Dotsource_Paymentoperator_Model_Payment_Abstract */
        $methodInstance = $payment->getMethodInstance();
        if ($methodInstance instanceof Dotsource_Paymentoperator_Model_Payment_Abstract
            && $methodInstance->getPaymentInformationModel()->getFeaturedPaymentFields()
        ) {
            return array($payment, $methodInstance);
        }

        return null;
    }

    /**
     * Backup payment data.
     *
     * @param $observer
     */
    public function backupPaymentData(Varien_Event_Observer $observer)
    {
        //Check the method return an array we copy data
        $data = $this->_getMethodInstance($observer);
        if (!$data) {
            return;
        }
        list($payment, $methodInstance) = $data;

        //Create the backup
        $fieldsAsKey = array_flip(array_values($methodInstance->getPaymentInformationModel()->getFeaturedPaymentFields()));
        $payment->setData(
            Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENT_INFORMATION_BACKUP_KEY,
            array_intersect_key($payment->getData(), $fieldsAsKey)
        );

        $methodInstance->removePaymentInformation($payment);
    }

    /**
     * Restore the payment data.
     *
     * @param $observer
     */
    public function restorePaymentData(Varien_Event_Observer $observer)
    {
        //Check the method return an array we copy data
        $data = $this->_getMethodInstance($observer);
        if (!$data) {
            return;
        }
        list($payment, $methodInstance) = $data;

        //Restore
        $backup = $payment->getData(Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENT_INFORMATION_BACKUP_KEY);
        if (is_array($backup) && $backup) {
            $payment->addData($backup);
            $payment->setData(Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENT_INFORMATION_BACKUP_KEY, null);
        }
    }
}