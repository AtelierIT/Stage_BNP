<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * sklein - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Billpay_Directdebit_Infotext
    extends Mage_Core_Block_Template
{

    /**
     * @see Mage_Core_Block_Template::_construct()
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentoperator/info/billpay/directdebit/infotext.phtml');
    }

    /**
     * Return the pdf version of the info text.
     *
     * @return  string
     */
    public function toPdf()
    {
        $this->setTemplate('paymentoperator/info/pdf/billpay/directdebit/infotext.phtml');
        return $this->toHtml();
    }
}