<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Request_Klarna_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    const ARTICLE_FLAG_SHIPPING_FEE             = 8;
    const ARTICLE_FLAG_SHIPPING_FEE_WITH_TAX    = 40;
    const ARTICLE_FLAG_WITH_TAX                 = 32;
    const SKU_SHIPPING                          = 'shipping_cost';


    /**
     * Return a valid order desc for klarna with the given items.
     *
     * @return string
     */
    protected function _getOrderDesc()
    {
        $order              = $this->getOracle()->getModel();
        $items              = $this->getOracle()->getModel()->getAllVisibleItems();
        $shippingAmount     = (float)$order->getBaseShippingInclTax();
        $currencyCode       = $this->_getCurrencyCode();
        $converter          = $this->_getConverter();
        $itemInformation    = array();

        //Process all products
        foreach ($items as $item) {
            $itemInformation[] = array(
                'amount'        => $item->getQtyOrdered(),
                'sku'           => $item->getSku(),
                'name'          => $item->getName(),
                'price'         => $this->_getConverter()->formatPrice($item->getBasePriceInclTax(), $currencyCode),
                'tax'           => $item->getTaxPercent(),
                'discount'      => $this->_getItemDiscountInPercent($item),
                'article_flag'  => self::ARTICLE_FLAG_WITH_TAX,
            );
        }

        //Add shipping
        if ($shippingAmount) {
            $itemInformation[] = array(
                'amount'        => 1,
                'sku'           => self::SKU_SHIPPING,
                'name'          => $this->_getHelper()->__('Shipping'),
                'price'         => $converter->formatPrice($shippingAmount, $currencyCode),
                'tax'           => $this->_getShippingRateTaxPercent(),
                'discount'      => $this->_getShippingDiscountInPercent(),
                'article_flag'  => self::ARTICLE_FLAG_SHIPPING_FEE_WITH_TAX,
            );
        }

        return $this->_formatInformationArrayToOrderDesc($itemInformation);
    }


    /**
     * Formats the item information in the order desc form.
     *
     * @param array $itemInformation
     * @return string
     */
    protected function _formatInformationArrayToOrderDesc(array &$itemInformation)
    {
        //Now format the product informations in the right format
        foreach ($itemInformation as $key => &$value) {
            //Escape the values from the $value array
            $value = array_map(array($this, '_escapeOrderDescValue'), $value);

            //Rewrite the array $value
            $itemInformation[$key] = implode(';', $value);
        }

        //Return the complete order desc as string
        return implode('+', $itemInformation);
    }


    /**
     * Return the discount in percent of the given product.
     *
     * @param $item
     * @return string
     */
    protected function _getItemDiscountInPercent($item)
    {
        return $this->_getDiscountInPercent(
            $item->getBaseDiscountAmount(),
            $item->getBaseRowTotalInclTax()
        );
    }


    /**
     * Calculate the shipping cost discount in percent.
     *
     * @return string
     */
    protected function _getShippingDiscountInPercent()
    {
        $order = $this->getOracle()->getModel();
        return $this->_getDiscountInPercent(
            $order->getBaseShippingDiscountAmount(),
            $order->getBaseShippingInclTax()
        );
    }


    /**
     * Return the discount in percent from the given discount and base price.
     *
     * @param mixed $discount
     * @param mixed $price
     */
    protected function _getDiscountInPercent($discountPrice, $basePrice)
    {
        if ($discountPrice) {
            return (string)round(($discountPrice / $basePrice) * 100.0, 8);
        }

        return '0';
    }


    /**
     * Return the shipping tax amount.
     *
     * @return float
     */
    protected function _getShippingRateTaxPercent()
    {
        $storeId            = $this->getOracle()->getModel()->getStoreId();
        $shippingTaxClass   = Mage::helper('tax')->getShippingTaxClass($storeId);

        $rateRequest = Mage::getSingleton('tax/calculation')->getRateRequest(
            $this->getOracle()->getShippingAddress(),
            $this->getOracle()->getBillingAddress(),
            $this->getOracle()->getModel()->getCustomerTaxClassId(),
            $storeId
        );

        $rateRequest->setStore($this->getOracle()->getModel()->getStore());
        $rateRequest->setProductClassId($shippingTaxClass);
        return Mage::getSingleton('tax/calculation')->getRate($rateRequest);
    }


    /**
     * Convert the given list of chars/strings in the array of $convertChars to
     * the given char/string in $toChar.
     *
     * @param $data
     * @param $toChar
     * @param $convertChars
     * @return string
     */
    protected function _escapeOrderDescValue(&$data, $toChar = '-', array $convertChars = null)
    {
        if (null === $convertChars) {
            static $convertChars = array('<', '>');
        }

        return str_replace($convertChars, $toChar, $data);
    }
}