<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Paymentreceiver
    extends Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract
{

    /**
     * Return the eft receiver owner.
     *
     * @return  string|null
     */
    public function getEftReceiverOwner()
    {
        return $this->getResponse()->getData('bpaccowner');
    }

    /**
     * Return the eft receiver ban.
     *
     * @return  string|null
     */
    public function getEftReceiverBan()
    {
        return $this->getResponse()->getData('bpaccnr');
    }

    /**
     * Return the eft receiver bcn.
     *
     * @return  string|null
     */
    public function getEftReceiverBcn()
    {
        return $this->getResponse()->getData('bpacciban');
    }

    /**
     * Return the eft bank name.
     *
     * @return  string|null
     */
    public function getEftReceiverBankName()
    {
        return $this->getResponse()->getData('bpbank');
    }

    /**
     * Return the eft invoice reference.
     *
     * @return  string|null
     */
    public function getEftReceiverInvoiceReference()
    {
        return $this->getResponse()->getData('bpinvoiceref');
    }

    /**
     * Return the eft invoice date.
     *
     * @return  string|null
     */
    public function getEftReceiverInvoiceDate()
    {
        return $this->getResponse()->getData('bpinvoicedate');
    }
}