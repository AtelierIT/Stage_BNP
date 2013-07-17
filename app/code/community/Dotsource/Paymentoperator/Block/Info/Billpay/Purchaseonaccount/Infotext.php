<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * sklein - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Billpay_Purchaseonaccount_Infotext
    extends Mage_Core_Block_Template
{

    /**
     * @see Mage_Core_Block_Template::_construct()
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentoperator/info/billpay/purchaseonaccount/infotext.phtml');
    }

    /**
     * Return the pdf version of the info text.
     *
     * @return  string
     */
    public function toPdf()
    {
        $this->setTemplate('paymentoperator/info/pdf/billpay/purchaseonaccount/infotext.phtml');
        return $this->toHtml();
    }
}