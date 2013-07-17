<?php

class Bonaparte_ImportExport_Model_External_Relation_Attribute_Option extends Mage_Core_Model_Abstract
{
    const TYPE_ATTRIBUTE_OPTION = 'attribute_option';

    public function _construct()
    {
        parent::_construct();
        $this->_init('Bonaparte_ImportExport/External_Relation_Attribute_Option');
    }
}