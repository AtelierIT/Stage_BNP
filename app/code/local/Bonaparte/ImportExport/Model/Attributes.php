<?php

class Bonaparte_ImportExport_Model_Attributes extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('Bonaparte_ImportExport/Attributes');
    }
}