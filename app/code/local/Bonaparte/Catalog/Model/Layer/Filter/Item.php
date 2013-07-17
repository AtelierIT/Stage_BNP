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
 * Filter item model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Bonaparte_Catalog_Model_Layer_Filter_Item extends Varien_Object
{
    /**
     * Get filter instance
     *
     * @return Mage_Catalog_Model_Layer_Filter_Abstract
     */
    public function getFilter()
    {
        $filter = $this->getData('filter');
        if (!is_object($filter)) {
            Mage::throwException(
                Mage::helper('catalog')->__('Filter must be an object. Please set correct filter.')
            );
        }
        return $filter;
    }

    /**
     * Get filter item url
     *
     * @return string
     */
    public function getUrl()
    {
        $query = array(
            $this->getFilter()->getRequestVar()=>$this->getValue(),
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        return Mage::getUrl('*/*/*', array('_current'=>true, '_use_rewrite'=>true, '_query'=>$query));
    }

    /**
     * Get url for remove item from filter
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        $query = array($this->getFilter()->getRequestVar()=>$this->getFilter()->getResetValue());
        $params['_current']     = true;
        $params['_use_rewrite'] = true;
        $params['_query']       = $query;
        $params['_escape']      = true;
        return Mage::getUrl('*/*/*', $params);
    }

    /**
     * Get url for "clear" link
     *
     * @return false|string
     */
    public function getClearLinkUrl()
    {
        $clearLinkText = $this->getFilter()->getClearLinkText();
        if (!$clearLinkText) {
            return false;
        }

        $urlParams = array(
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => array($this->getFilter()->getRequestVar() => null),
            '_escape' => true,
        );
        return Mage::getUrl('*/*/*', $urlParams);
    }

    /**
     * Get item filter name
     *
     * @return string
     */
    public function getName()
    {

        if($this->getFilter()->getRequestVar() == 'bnp_size'){
            $a = 1;
            $configurableProducts = $this->getFilter()->getLayer()->getProductCollection()->getItems();
            $simpleIds = array();
            $storeID = Mage::app()->getStore()->getId();
            foreach($configurableProducts as $id=>$pr){
                $product = Mage::getModel('catalog/product')->load($id);
                $simpleIds[] = implode(",", Mage::getModel('catalog/product_type_configurable')->getUsedProductIds($product));
            }
            // $stringIds = implode(",",$simpleIds);
            $stringIds = $simpleIds[0];
            $query = "SELECT catalog_product_entity_int.value AS bnp_size_value, catalog_product_entity_varchar . value as size_stranslate
FROM `catalog_product_entity_varchar`
LEFT JOIN catalog_product_entity_int ON catalog_product_entity_int.entity_id = catalog_product_entity_varchar.entity_id
WHERE catalog_product_entity_varchar.attribute_id =517
AND catalog_product_entity_int.attribute_id =506
AND catalog_product_entity_varchar.store_id = $storeID
AND catalog_product_entity_varchar.entity_id
IN ( $stringIds )
GROUP BY catalog_product_entity_varchar.value";

            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $results = $readConnection->fetchAll($query);
            $ArrayTranslate = array();
            foreach($results as $value){
                $ArrayTranslate[$value['bnp_size_value']] = $value['size_stranslate'];
            }

            $this->setLabel($ArrayTranslate[$this->getValue()]);
        }


        return $this->getFilter()->getName();
    }

    /**
     * Get item value as string
     *
     * @return string
     */
    public function getValueString()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }
}
