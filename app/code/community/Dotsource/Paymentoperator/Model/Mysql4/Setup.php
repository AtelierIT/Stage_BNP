<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Mysql4_Setup
    extends Mage_Sales_Model_Mysql4_Setup
{


    /**
     * Return the flat table name from the given entity type.
     * If the given entity type is not a flat table the method
     * return null.
     *
     * @param string $entityType
     * @return string || null
     */
    public function getFlatTableName($entityType)
    {
        if ($this->isFlatTable($entityType)) {
            return $this->_flatEntityTables[$entityType];
        }

        return null;
    }


    /**
     * Return true if the entity type has a flat table.
     *
     * @return boolean
     */
    public function isFlatTable($entityType)
    {
        return isset($this->_flatEntityTables[$entityType]);
    }


    /**
     * Return true if the flat table exists in the database.
     *
     * @param string $entityType
     * @return boolean
     */
    public function flatTableExists($entityType)
    {
        return $this->isFlatTable($entityType)
            && $this->_flatTableExist($this->getFlatTableName($entityType));
    }


    /**
     * @see Mage_Eav_Model_Entity_Setup::removeAttribute()
     *
     * @param mixed $entityTypeId
     * @param mixed $code
     * @return Dotsource_Paymentoperator_Model_Mysql4_Setup
     */
    public function removeAttribute($entityTypeId, $code)
    {
        if ($this->flatTableExists($entityTypeId)) {
            $this->_removeFlatAttribute($this->getFlatTableName($entityTypeId), $code);
        } else {
            parent::removeAttribute($entityTypeId, $code);
        }

        return $this;
    }


    /**
     * Drop the given column from the given table.
     *
     * @param string $table
     * @param string $column
     * @return Dotsource_Paymentoperator_Model_Mysql4_Setup
     */
    public function _removeFlatAttribute($table, $column)
    {
        $tableInfo = $this->getConnection()->describeTable($this->getTable($table));
        if (isset($tableInfo[$column])) {
            return $this;
        }

        $this->getConnection()->dropColumn($this->getTable($table), $column);
        return $this;
    }


    /**
     * Add a Grid Attribute.
     *
     * @param $table
     * @param $attribute
     * @param $attr
     * @param $entityTypeId
     */
    public function addFlatGridAttribute($entityTypeId, $code, array $attr)
    {
        //Check if a flat table exists
        if ($this->isFlatTable($entityTypeId)) {
            //Force add grid
            $attr['grid'] = true;

            //Create new grid attribute
            $this->_addGridAttribute($this->getFlatTableName($entityTypeId), $code, $attr, $entityTypeId);
        }

        return $this;
    }
}