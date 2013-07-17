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
 * Layer attribute filter
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Bonaparte_Catalog_Model_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
    const OPTIONS_ONLY_WITH_RESULTS = 1;

    /**
     * Resource instance
     *
     * @var Mage_Catalog_Model_Resource_Eav_Mysql4_Layer_Filter_Attribute
     */
    protected $_resource;

    /**
     * Construct attribute filter
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_requestVar = 'attribute';
    }

    /**
     * Retrieve resource instance
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Layer_Filter_Attribute
     */
    protected function _getResource()
    {
        if (is_null($this->_resource)) {
            $this->_resource = Mage::getResourceModel('catalog/layer_filter_attribute');
        }
        return $this->_resource;
    }

    /**
     * Get option text from frontend model by option id
     *
     * @param   int $optionId
     * @return  string|bool
     */
    protected function _getOptionText($optionId)
    {
        return $this->getAttributeModel()->getFrontend()->getOption($optionId);
    }

    /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Varien_Object $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter)) {
            return $this;
        }
        $text = $this->_getOptionText($filter);
        if ($filter && strlen($text)) {
            $this->_getResource()->applyFilterToCollection($this, $filter);
            $this->getLayer()->getState()->addFilter($this->_createItem($text, $filter));
            $this->_items = array();
        }
        return $this;
    }

    /**
     * Check whether specified attribute can be used in LN
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return bool
     */
    protected function _getIsFilterableAttribute($attribute)
    {
        return $attribute->getIsFilterable();
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        
		$attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        $key = $this->getLayer()->getStateKey().'_'.$this->_requestVar;
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        // getUsedProductCollection getUsedProductIds
        if($this->getRequestVar() == 'bnp_size'){
            $configurableProducts = $this->getLayer()->getProductCollection()->getItems();
            $simpleIds = array();
            $storeID = Mage::app()->getStore()->getId();;
            foreach($configurableProducts as $id=>$pr){
                $product = Mage::getModel('catalog/product')->load($id);
                $simpleIds[] = implode(",", Mage::getModel('catalog/product_type_configurable')->getUsedProductIds($product));


            }
            $stringIds = implode(",",$simpleIds);

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

            //$resource = Mage::getSingleton('core/resource');
            //$readConnection = $resource->getConnection('core_read');
            //$translateSizeArray = $readConnection->fetchAll($query);

        }



        //$_productCollection1 = Mage::getResourceModel('catalog/product_collection')
        //    ->addAttributeToSelect('*')
        //    ->addAttributeToFilter('type_id','simple');
        //$count = count($_productCollection1);

        if ($data === null) {
            $options = $attribute->getFrontend()->getSelectOptions(); /* Inlocuit cu select translate label size si doar pe stoc*/
            $optionsCount = $this->_getResource()->getCount($this);
            $data = array();
            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    continue;
                }
                if (Mage::helper('core/string')->strlen($option['value'])) {
                    // Check filter type
                    if ($this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
                        if (!empty($optionsCount[$option['value']])) {

                            if($ArrayTranslate[$option['value']]){
                                $data[] = array(
                                    'label' => $ArrayTranslate[$option['value']],
                                    'value' => $option['value'],
                                    'count' => $optionsCount[$option['value']],
                                );

                            } else {
                                $data[] = array(
                                    'label' => $option['label'],
                                    'value' => $option['value'],
                                    'count' => $optionsCount[$option['value']],
                                );
                            }


                        }
                    }
                    else {
                        if($ArrayTranslate[$option['value']]){
                            $data[] = array(
                                'label' => $ArrayTranslate[$option['value']],
                                'value' => $option['value'],
                                'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
                            );
                        } else {
                            $data[] = array(
                                'label' => $option['label'],
                                'value' => $option['value'],
                                'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
                            );
                        }

                    }
                }
            }

            $tags = array(
                Mage_Eav_Model_Entity_Attribute::CACHE_TAG.':'.$attribute->getId()
            );

            $tags = $this->getLayer()->getStateTags($tags);
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        return $data;
    }
}
