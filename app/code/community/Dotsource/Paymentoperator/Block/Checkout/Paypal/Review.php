<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.05.2010 10:27:46
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Block_Checkout_Paypal_Review
    extends Mage_Core_Block_Template
{
    /**
     * Holds the current processed quote.
     *
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote;

    /**
     * Paypal action prefix.
     *
     * @var string
     */
    protected $_paypalActionPrefix = 'paypal';

    /**
     * Holds the address based on the shipping address.
     *
     * @var Mage_Sales_Model_Quote_Address
     */
    protected $_address;

    /**
     * Holds the shippingsrates as array.
     *
     * @var  array
     */
    protected $_rates;


    /**
     * Set quote for internal use.
     *
     * @param   Mage_Sales_Model_Quote  $quote
     * @return  Dotsource_Paymentoperator_Block_Checkout_Paypal_Review
     */
    public function setQuote(Mage_Sales_Model_Quote $quote)
    {
        $this->_quote = $quote;
        return $this;
    }


    /**
     * Retrieves the billing address of the current quote.
     *
     * @return  Mage_Sales_Model_Quote_Address|false
     */
    public function getBillingAddress()
    {
        return $this->_quote->getBillingAddress();
    }


    /**
     * Retrieves the shipping address of the current quote.
     *
     * @return Mage_Sales_Model_Quote_Address|false
     */
    public function getShippingAddress()
    {
        if ($this->_quote->getIsVirtual()) {
            return false;
        }
        return $this->_quote->getShippingAddress();
    }


    /**
     * Return address base on quote shipping address
     *
     * @return Mage_Sales_Quote_Address
     */
    public function getAddress()
    {
        if (null === $this->_address) {
            $this->_address = $this->_quote->getShippingAddress();
        }
        return $this->_address;
    }


    /**
     * Retrieves the shipping rates.
     *
     * @return  array
     */
    public function getShippingRates()
    {
        if (null === $this->_rates) {
            $this->_rates = (array)$this->getAddress()->getGroupedAllShippingRates();
        }
        return $this->_rates;
    }


    /**
     * Retrieves carrier name from config, base on carrier code
     *
     * @param $carrierCode string
     * @return string
     */
    public function getCarrierName($carrierCode)
    {
        if ($name = Mage::getStoreConfig('carriers/'.$carrierCode.'/title')) {
            return $name;
        }
        return $carrierCode;
    }


    /**
     * get shipping method
     *
     * @return  string
     */
    public function getAddressShippingMethod()
    {
        return $this->getAddress()->getShippingMethod();
    }


    /**
     * Retrieves the formated shipping price.
     *
     * @param   float   $price
     * @param   boolean $flag
     *
     * @return  string
     */
    public function getShippingPrice($price, $flag)
    {
        return $this->formatPrice($this->helper('tax')->getShippingPrice($price, $flag, $this->getAddress()));
    }


    /**
     * Format price base on store convert price method.
     *
     * @param   float   $price
     * @return  string
     */
    public function formatPrice($price)
    {
        return $this->_quote->getStore()->convertPrice($price, true);
    }


    /**
     * Indicates if the quote is virtual.
     *
     * @return  boolean
     */
    public function isVirtual()
    {
        return (bool)$this->_quote->getIsVirtual();
    }


    /**
     * Retrieves the url to the saveShippingMethodAction.
     *
     * @return  string
     */
    public function getSaveShippingMethodUrl()
    {
        return $this->getUrl('paymentoperator/callback_paypalexpress/saveShippingMethod');
    }


    /**
     * Retrieves the url to the saveShippingMethodAction.
     *
     * @return  string
     */
    public function getPlaceOrderUrl()
    {
        return $this->getUrl('paymentoperator/callback_paypalexpress/placeOrder');
    }
}
