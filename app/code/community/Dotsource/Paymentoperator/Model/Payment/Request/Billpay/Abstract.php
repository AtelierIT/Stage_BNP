<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /**
     * Return the article list informations.
     *
     * @return  string
     */
    protected function _getArticleList()
    {
        /* @var $item           Mage_Sales_Model_Order_Item */
        /* @var $stringHelper   Mage_Core_Helper_String */
        $items              = $this->getOracle()->getModel()->getAllVisibleItems();
        $currencyCode       = $this->_getCurrencyCode();
        $converter          = $this->_getConverter();
        $stringHelper       = Mage::helper('core/string');
        $itemInformations   = array();

        //TODO: Order Item has not getProduct() method

        //Process all products
        foreach ($items as $item) {
            $shortDescription = trim(strip_tags(nl2br($item->getProduct()->getShortDescription())));

            $itemInformations[] = array(
                'sku'               => $stringHelper->truncate($item->getSku(), 20),
                'amount'            => ($this->getOracle()->isOrder())? $item->getQtyOrdered() : $item->getQty(),
                'name'              => $stringHelper->truncate($item->getName(), 50),
                'short_description' => $stringHelper->truncate($shortDescription, 50),
                'price_net'         => $converter->formatPrice($item->getBasePrice(), $currencyCode),
                'price_gross'       => $converter->formatPrice($item->getBasePriceInclTax(), $currencyCode),
            );
        }

        return $this->_formatInformation($itemInformations);
    }

    /**
     * Return the order desc informations.
     *
     * @return  string
     */
    protected function _getOrderDesc()
    {
        /* @var $stringHelper Mage_Core_Helper_String */
        $order                  = $this->getOracle()->getModel();
        $currencyCode           = $this->_getCurrencyCode();
        $converter              = $this->_getConverter();
        $stringHelper           = Mage::helper('core/string');

        //Get the discount as positive value
        $baseDiscount           = abs(min($this->getOracle()->getBaseDiscountAmount(), 0));
        $baseDiscountExcludeTax = abs(min($this->getOracle()->getBaseDiscountAmountExclTax(), 0));

        $information = array(
            'shipping_name'         => $stringHelper->truncate($this->getOracle()->getShippingDescription(), 50),
            'shipping_price_net'    => $converter->formatPrice($this->getOracle()->getBaseShippingAmount(), $currencyCode),
            'shipping_price_gross'  => $converter->formatPrice($this->getOracle()->getBaseShippingAmountInclTax(), $currencyCode),
            'discount_amount_net'   => $converter->formatPrice($baseDiscountExcludeTax, $currencyCode),
            'discount_amount_gross' => $converter->formatPrice($baseDiscount, $currencyCode),
            'grand_total_net'       => $converter->formatPrice($this->getOracle()->getBaseGrandTotalExclTax(), $currencyCode),
            'grand_total_gross'     => $converter->formatPrice($this->getOracle()->getBaseGrandTotal(), $currencyCode),
        );

        return $this->_formatInformation(array($information));
    }

    /**
     * Formats the given informations.
     *
     * @param   array   $itemInformation
     * @return  string
     */
    protected function _formatInformation(array $informations)
    {
        //Escape all information in the array
        foreach ($informations as $singleInformationKey => $singleInformationArray) {
            foreach ($singleInformationArray as $informationKey => $informationValue) {
                $informations[$singleInformationKey][$informationKey] = $this->_escapeValue($informationValue);
            }

            //Join the single information array as string with the ";" as separator
            $informations[$singleInformationKey] = implode(';', $singleInformationArray);
        }

        //Join and return the complete information as string
        return implode('+', $informations);
    }

    /**
     * Convert the given list of chars/strings in the array of $convertChars to
     * the given char/string in $toChar.
     *
     * @param   string  $data
     * @param   string  $toChar
     * @param   array   $convertChars
     * @return  string
     */
    protected function _escapeValue($data, $toChar = '-', array $convertChars = array('+', ';'))
    {
        return str_replace($convertChars, $toChar, $data);
    }
}