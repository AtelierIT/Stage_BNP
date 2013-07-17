<?php

/**
 * Catalog product view block
 *
 * @category    Bonaparte
 * @package     Bonaparte_Catalog
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_Catalog_Block_Product_View extends Mage_Catalog_Block_Product_View {

    /**
     * Product type id
     *
     * @var string
     */
    const PRODUCT_TYPE_ID = 'configurable';

    /**
     * Retrieves the products with the same style
     *
     * @param boolean $exceptCurrentProduct
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getStyleProducts($exceptCurrentProduct = true) {
        $currentProduct = $this->getProduct();

        $collection = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToFilter('bnp_stylenbr', array('eq' => $currentProduct->getBnpStylenbr()))
                        ->addAttributeToFilter('type_id', array('eq' => self::PRODUCT_TYPE_ID))
                        ->addAttributeToFilter('status',array('eq' => $currentProduct->getStatus()));

        if($exceptCurrentProduct) {
            $collection->addAttributeToFilter('entity_id', array('neq' => $currentProduct->getId()));
        }

        return $collection;
    }

}