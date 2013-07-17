<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Billpay_Purchaseonaccount
    extends Dotsource_Paymentoperator_Block_Info_Billpay_Abstract
{

    /**
     * Add a new block to show the additional payment informations.
     */
    protected function _toHtml()
    {
        //Build the block
        $block = Mage::app()->getLayout()->createBlock("paymentoperator/info_billpay_purchaseonaccount_infotext")
                    ->setInvoiceDate($this->getInvoiceDate())
                    ->setInvoiceReference($this->getEftReceiverInvoiceReference());

        //Set the block as child
        if ($this->getIsSecureMode() && $this->isInvoiceContext()) {
            $this->setChild('paymentoperator_billpay_ratepay_purchaseonaccount_infotext', $block);
        }

        return parent::_toHtml();
    }

    /**
     * Return the customers due date.
     *
     * @return  string
     */
    public function getInvoiceDate()
    {
        $dueDate = $this->getInfo()->getMethodInstance()->getEftReceiverInvoiceDate();
        return $dueDate;
    }

    /**
     * Return the intended prupose.
     *
     * @return  string
     */
    public function getEftReceiverInvoiceReference()
    {
        $dueDate = $this->getInfo()->getMethodInstance()->getEftReceiverInvoiceReference();
        return $dueDate;
    }
}