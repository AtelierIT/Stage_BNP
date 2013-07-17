<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Form_Billpay_Ratepay
    extends Dotsource_Paymentoperator_Block_Form_Billpay_Abstract
{

    /**
     * Add the additional payment information to the block and return the html.
     *
     * @return  string
     */
    protected function _toHtml()
    {
        //Add additional block for eft data
        /* @var $eftBlock Dotsource_Paymentoperator_Block_Form_Billpay_Eft */
        $eftBlock = Mage::app()->getLayout()->createBlock('paymentoperator/form_billpay_eft');
        $eftBlock
            ->setMethod($this->getMethod())
            ->setTemplate('paymentoperator/form/billpay/eft.phtml');
        $this->setChild('billpay_additional_payment_information', $eftBlock);

        return parent::_toHtml();
    }
}