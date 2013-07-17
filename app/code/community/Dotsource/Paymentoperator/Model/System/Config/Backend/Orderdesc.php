<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * ssl - initial contents
**/

class Dotsource_Paymentoperator_Model_System_Config_Backend_Orderdesc
    extends Mage_Core_Model_Config_Data
{

    protected $_blacklistChars = array(
        '"', "'", '+', '*', '#', '=', '&', '?', '%', '>', '<', '|', '~', '“'
    );

    /**
     * @see Mage_Core_Model_Config_Data::_beforeSave()
     */
    protected function _beforeSave()
    {
        //Get the data
        $value = $this->getValue();

        //replace blacklist chars
        $value = str_replace($this->_blacklistChars, '', $value);

        //Set the value again
        $this->setValue($value);

        //Call the parents
        parent::_beforeSave();
    }

}