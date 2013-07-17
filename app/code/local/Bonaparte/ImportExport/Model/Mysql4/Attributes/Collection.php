<?php

class Bonaparte_ImportExport_Model_Mysql4_Attributes_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('Bonaparte_ImportExport/Attributes');
    }
}