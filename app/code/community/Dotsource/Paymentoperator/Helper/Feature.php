<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Feature
    extends Mage_Core_Helper_Abstract
{

    /** Holds if the current magento instance has flat tables */
    protected $_hasFlatTables = null;

    /** Holds if the current magento instance has grid tables */
    protected $_hasGridTables = null;

    /** Holds if the current magento instance has grid tables */
    protected $_hasOrderStatusTables = null;


    /**
     * Return if the current magento instance has support for flat tables.
     *
     * @return boolean
     */
    public function hasFlatTables()
    {
        if (null === $this->_hasFlatTables) {
            //TODO: Don't compare magento version check the database for sales_flat_order
            $this->_hasFlatTables = version_compare(Mage::getVersion(), '1.4.1.0', '>=');
        }

        return $this->_hasFlatTables;
    }


    /**
     * Return true if the current magento instance has support for grid tables.
     *
     * @return boolean
     */
    public function hasGridTables()
    {
        if (null === $this->_hasGridTables) {
            $this->_hasGridTables = (boolean)@Mage::getResourceSingleton('sales/order_grid_collection');
        }

        return $this->_hasGridTables;
    }

    /**
     * Return true if the magento version has order status tables.
     *
     * @return boolean
     */
    public function hasOrderStatusTables()
    {
        if (null === $this->_hasOrderStatusTables) {
            $this->_hasOrderStatusTables = (boolean)@Mage::getResourceSingleton('sales/order_status_collection');
        }

        return $this->_hasOrderStatusTables;
    }
}