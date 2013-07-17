<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.05.2010 14:31:10
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Block_Checkout_Paypal_Review_Details
    extends Mage_Checkout_Block_Cart_Totals
{
    protected $_address;

    /**
     * Return review shipping address
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getAddress()
    {
        if (empty($this->_address)) {
            $this->_address = $this->getQuote()->getShippingAddress();
        }
        return $this->_address;
    }

    /**
     * Return review quote totals
     *
     * @return array
     */
    public function getTotals()
    {
        return $this->getQuote()->getTotals();
    }
}
