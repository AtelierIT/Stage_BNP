<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product type price model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Max
 */
class Bonaparte_Catalog_Model_Product_Type_Configurable_Price extends Mage_Catalog_Model_Product_Type_Price
{
    /**
     * Get product final price
     *
     * @param   double $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  double
     */
    public function getFinalPrice($qty=null, $product)
    {
        $NewProduct = $product;
        $my_prod = $product->getCustomOptions();

        if($my_prod['simple_product']){
            $my_prod = $my_prod['simple_product'];
            $my_prodId = $my_prod->getProduct()->getEntityId();
            $NewProduct = Mage::getModel('catalog/product')->load($my_prodId);
        }

        if (is_null($qty) && !is_null($NewProduct->getCalculatedFinalPrice())) {
            return $NewProduct->getCalculatedFinalPrice();
        }

        $basePrice = $this->getBasePrice($NewProduct, $qty);
        $finalPrice = $basePrice;
        $NewProduct->setFinalPrice($finalPrice);
        Mage::dispatchEvent('catalog_product_get_final_price', array('product' => $NewProduct, 'qty' => $qty));
        $finalPrice = $NewProduct->getData('final_price');

        $finalPrice += $this->getTotalConfigurableItemsPrice($product, $finalPrice);
        $finalPrice += $this->_applyOptionsPrice($NewProduct, $qty, $basePrice) - $basePrice;
        $finalPrice = max(0, $finalPrice);

        $NewProduct->setFinalPrice($finalPrice);
        return $finalPrice;
    }

    /**
     * Get Total price for configurable items
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $finalPrice
     * @return float
     */
    public function getTotalConfigurableItemsPrice($product, $finalPrice)
    {
        $price = 0.0;
        $my_prod = $product;
        $product->getTypeInstance(true)
            ->setStoreFilter($product->getStore(), $product);
        $attributes = $product->getTypeInstance(true)
            ->getConfigurableAttributes($product);

        $selectedAttributes = array();
        if ($product->getCustomOption('attributes')) {
            $selectedAttributes = unserialize($product->getCustomOption('attributes')->getValue());
        }

        foreach ($attributes as $attribute) {
            $attributeId = $attribute->getProductAttribute()->getId();
            $value = $this->_getValueByIndex(
                $attribute->getPrices() ? $attribute->getPrices() : array(),
                isset($selectedAttributes[$attributeId]) ? $selectedAttributes[$attributeId] : null
            );
            $product->setParentId(true);
            if ($value) {
                if ($value['pricing_value'] != 0) {
                    $product->setConfigurablePrice($this->_calcSelectionPrice($value, $finalPrice));
                    Mage::dispatchEvent(
                        'catalog_product_type_configurable_price',
                        array('product' => $product)
                    );
                    $price += $product->getConfigurablePrice();
                }
            }
        }
        return $price;
    }

    /**
     * Calculate configurable product selection price
     *
     * @param   array $priceInfo
     * @param   decimal $productPrice
     * @return  decimal
     */
    protected function _calcSelectionPrice($priceInfo, $productPrice)
    {
        if($priceInfo['is_percent']) {
            $ratio = $priceInfo['pricing_value']/100;
            $price = $productPrice * $ratio;
        } else {
            $price = $priceInfo['pricing_value'];
        }
        return $price;
    }

    protected function _getValueByIndex($values, $index) {
        foreach ($values as $value) {
            if($value['value_index'] == $index) {
                return $value;
            }
        }
        return false;
    }

    /** New modification for best simple price to configurable products **/

    public function getSimpleProductsPices($product){
        $prices = array();
        $childProducts = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null,$product);
        foreach($childProducts as $child) {
             $prices[] = $child->getFinalPrice();
        }
        sort($prices);
        return $prices;
    }

    public function getPrice($product)
    {	
        if($product->getType_id()== 'configurable'){
            $myPrice = $this->getSimpleProductsPices($product);
            return $myPrice[0];
        }
        return $product->getData('price');
    }


}
