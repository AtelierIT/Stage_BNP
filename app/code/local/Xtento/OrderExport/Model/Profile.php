<?php

/**
 * Product:       Xtento_OrderExport (1.2.4)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2013-02-18T16:43:46+01:00
 * File:          app/code/local/Xtento/OrderExport/Model/Profile.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Model_Profile extends Mage_Rule_Model_Rule
{
    /*
     * Profile model containing information about export profiles
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('xtento_orderexport/profile');
    }

    public function getConditionsInstance()
    {
        Mage::register('profile', $this, true);
        return Mage::getModel('xtento_orderexport/export_condition_combine');
    }

    public function getDestinations()
    {
        $logEntry = Mage::registry('export_log');
        $destinationIds = array_filter(explode("&", $this->getData('destination_ids')));
        $destinations = array();
        foreach ($destinationIds as $destinationId) {
            if (!is_numeric($destinationId)) {
                continue;
            }
            $destination = Mage::getModel('xtento_orderexport/destination')->load($destinationId);
            if ($destination->getId()) {
                $destinations[] = $destination;
            } else {
                if ($logEntry) {
                    $logEntry->setResult(Xtento_OrderExport_Model_Log::RESULT_WARNING);
                    $logEntry->addResultMessage(Mage::helper('xtento_orderexport')->__('Destination ID "%s" could not be found.', $destinationId));
                }
            }
        }
        if ($this->getSaveFilesLocalCopy()) {
            // Add "faked" local destination to save copies of all exports in ./var/export_bkp/
            $destination = Mage::getModel('xtento_orderexport/destination');
            $destination->setBackupDestination(true);
            $destination->setName("Backup Local Destination");
            $destination->setType(Xtento_OrderExport_Model_Destination::TYPE_LOCAL);
            $destination->setPath(Mage::helper('xtento_orderexport/export')->getExportBkpDir());
            $destinations[] = $destination;
        }
        // Return destinations
        return $destinations;
    }

    public function getLastExportedIncrementId()
    {
        $historyCollection = Mage::getResourceModel('xtento_orderexport/history_collection');
        $historyCollection->addFieldToFilter('main_table.profile_id', $this->getId());
        $historyCollection->getSelect()->order('main_table.entity_id DESC');
        $historyCollection->getSelect()->limit(1);
        if ($this->getEntity() == Xtento_OrderExport_Model_Export::ENTITY_QUOTE) {
            $historyCollection->getSelect()->joinLeft(array('object' => $historyCollection->getTable('sales/' . $this->getEntity())), 'main_table.entity_id = object.entity_id', array('object.entity_id'));
        } else if ($this->getEntity() == Xtento_OrderExport_Model_Export::ENTITY_CUSTOMER) {
            $historyCollection->getSelect()->joinLeft(array('object' => $historyCollection->getTable('customer/entity')), 'main_table.entity_id = object.entity_id', array('object.entity_id'));
        } else {
            $historyCollection->getSelect()->joinLeft(array('object' => $historyCollection->getTable('sales/' . $this->getEntity())), 'main_table.entity_id = object.entity_id', array('object.increment_id'));
        }
        $object = $historyCollection->getFirstItem();
        return ($object->getIncrementId() ? $object->getIncrementId() : $object->getId());
    }
}