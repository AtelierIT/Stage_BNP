<?php

class Bonaparte_ImportExport_Model_Mysql4_Catalogue extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('Bonaparte_ImportExport/Catalogue', 'id');
    }
}