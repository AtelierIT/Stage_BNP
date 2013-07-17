<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Form_Eft
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        //Template
        $this->setTemplate('paymentoperator/form/eft.phtml');
    }

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        //No logo
    }

    /**
     * Return the payment information field from the last order with the
     * current payment method.
     *
     * @param string $field
     * @return string
     */
    protected function _getLastPaymentInformation($field, $escapeHtml = true)
    {
        //Get the last payment information from the order with the payment method
        $lastPaymentInformation = $this->getMethod()->getLastPaymentInformation();

        //Get the data
        if ($lastPaymentInformation && ($data = $lastPaymentInformation->getData($field))) {
            if ($escapeHtml) {
                $data = $this->escapeHtml($data);
            }

            return $data;
        }

        return "";
    }

    /**
     * Return the eft owner from the info instance.
     * If the info instance has no data and pre-fill is active
     * the last payment information will be used.
     *
     * @return string
     */
    public function getEftOwner()
    {
        //Check first the payment info object
        $eftOwner = $this->getInfoData('eft_owner');

        //Check if we should pre-fill the information
        if (!$eftOwner && $this->getMethod()->getConfigData('prefill_paymentinformation')) {
            //Return the last payment information
            return $this->_getLastPaymentInformation('eft_owner');
        }

        return $eftOwner;
    }

    /**
     * Return the eft ban (bank account number).
     *
     * @return string
     */
    public function getEftBan()
    {
        //Get the encrypted ban (not use getInfoData -> this methods escapes the content)
        $ban = $this->getMethod()->getInfoInstance()->getData('eft_ban_enc');

        //Check if we should pre-fill the information
        if (!$ban && $this->getMethod()->getConfigData('prefill_paymentinformation')) {
            $ban = $this->_getLastPaymentInformation('eft_ban_enc', false);
        }

        //Decrypt the ban
        if ($ban) {
            $ban = $this->getMethod()->getInfoInstance()->decrypt($ban);
            if ($ban) {
                return $this->escapeHtml($ban);
            }
        }

        return "";
    }

    /**
     * Return the eft bcn (bank code number).
     *
     * @return string
     */
    public function getEftBcn()
    {
        //Get the data from the info instance
        $eftBcn = $this->getInfoData('eft_bcn');

        //Check if we should pre-fill the information
        if (!$eftBcn && $this->getMethod()->getConfigData('prefill_paymentinformation')) {
            //Return the last payment information
            return $this->_getLastPaymentInformation('eft_bcn');
        }

        return $eftBcn;
    }
}