<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Response_Response
{

    /**
     * Return the current payid.
     *
     * @return  string|null
     */
    public function getPayid()
    {
        return $this->getResponse()->getData('payid');
    }

    /**
     * Return the current xid.
     *
     * @return  string|null
     */
    public function getXid()
    {
        return $this->getResponse()->getData('xid');
    }

    /**
     * Return the billpay transaction id.
     *
     * @return  string|null
     */
    public function getBillpayTransactionId()
    {
        return $this->getResponse()->getData('bptransactionid');
    }

    /**
     * Return the error message for the merchant.
     *
     * @return  string|null
     */
    public function getMerchantErrorText()
    {
        return $this->getResponse()->getData('errortext1');
    }

    /**
     * Return the error message for the customer.
     *
     * @return  string|null
     */
    public function getCustomerErrorText()
    {
        return $this->getResponse()->getData('errortext2');
    }

    /**
     * Return the billpay error code.
     *
     * @return  string|null
     */
    public function getBillpayErrorCode()
    {
        return $this->getResponse()->getData('errortext3');
    }

    /**
     * Return the billpay helper.
     *
     * @return  Dotsource_Paymentoperator_Helper_Billpay_Rate
     */
    protected function _getBillpayHelper()
    {
        return Mage::helper('paymentoperator/billpay_rate');
    }
}