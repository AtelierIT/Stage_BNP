<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Form_Billpay_Eft
    extends Dotsource_Paymentoperator_Block_Form_Billpay_Abstract
{

    /**
     * Return the eft owner.
     *
     * @return string|null
     */
    public function getBillpayEftOwner()
    {
        return $this->getMethod()->getEftOwner();
    }

    /**
     * Return the customer eft bank account number.
     *
     * @return string|null
     */
    public function getBillpayEftBan()
    {
        return $this->getMethod()->getEftBan();
    }

    /**
     * Return the customer eft bank code number.
     *
     * @return string|null
     */
    public function getBillpayEftBcn()
    {
        return $this->getMethod()->getEftBcn();
    }

    protected function _initLogos()
    {
        //No need
    }
}