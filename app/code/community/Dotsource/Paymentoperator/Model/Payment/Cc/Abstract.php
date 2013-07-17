<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Cc_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{

    /**
     * Override for checking the expire date.
     *
     * @return Varien_Object || null
     */
    public function getLastPaymentInformation()
    {
        $data = parent::getLastPaymentInformation();
        if ($data && $this->_validateExpDate($data->getCcExpYear(), $data->getCcExpMonth())) {
            return $data;
        }

        return null;
    }

    /**
     * Copied from Mage_Payment_Model_Method_Cc::_validateExpDate
     *
     * @param integer $expYear
     * @param integer $expMonth
     */
    protected function _validateExpDate($expYear, $expMonth)
    {
        $date = Mage::app()->getLocale()->date();
        if (!$expYear
            || !$expMonth
            || $date->compareYear($expYear) == 1
            || ($date->compareYear($expYear) == 0 && $date->compareMonth($expMonth) == 1)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Abstract::canBackendDirectCapture()
     *
     * @return boolean
     */
    public function canBackendDirectCapture()
    {
        //Check if we support direct capture
        if (parent::canBackendDirectCapture() && $this->isUsePseudoDataActive()) {
            $data   = array();
            $data[] = $this->getInfoInstance()->getCcNumberEnc();
            $data[] = $this->getInfoInstance()->getCcExpYear();
            $data[] = $this->getInfoInstance()->getCcExpMonth();
            $data[] = $this->getInfoInstance()->getCcType();

            //Check the data
            foreach ($data as $checkData) {
                if (empty($checkData)) {
                    return false;
                }
            }

            //No empty data
            return true;
        }

        return false;
    }

    /**
     * Return true if the pseudo cc data should use.
     *
     * @param mixed $storeId
     * @return boolean
     */
    public function isUsePseudoDataActive($storeId = null)
    {
        return (boolean) $this->getConfigData('use_pseudo_data', $storeId);
    }

    /**
     * Return true if the prefill information is active for cc.
     *
     * @param mixed $storeId
     * @return boolean
     */
    public function isPrefillinformationActive($storeId = null)
    {
        return (boolean) $this->getConfigData('prefill_paymentinformation', $storeId);
    }
}