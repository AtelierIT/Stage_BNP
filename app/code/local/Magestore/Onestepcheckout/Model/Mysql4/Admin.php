<?php

class Magestore_Onestepcheckout_Model_Mysql4_Admin extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {   
        $this->_init('onestepcheckout/admin', 'admin_id');
    }
}