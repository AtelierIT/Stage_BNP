<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Backend_Serialized_Merchant
    extends Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    /**
     * @see Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array::_beforeSave()
     */
    protected function _beforeSave()
    {
        //Get the data
        $value = $this->getValue();

        //Check if we have data
        if (!empty($value) && is_array($value)) {
            foreach ($value as &$row) {
                //Check if we have data and have all fields
                if (empty($row)) {
                    continue;
                }

                //Check if we need to encrypt the password
                if (!array_key_exists('original_password', $row)
                    || $row['password'] != $row['original_password']
                ) {
                    $row['password']            = Mage::helper('core')->encrypt($row['password']);
                    $row['original_password']   = $row['password'];
                }

                //Check if we need to encrypt the hmac
                if (!array_key_exists('original_hmac', $row)
                    || $row['hmac'] != $row['original_hmac']
                ) {
                    $row['hmac']            = Mage::helper('core')->encrypt($row['hmac']);
                    $row['original_hmac']   = $row['hmac'];
                }
            }
        }

        //Set the value again
        $this->setValue($value);

        //Call the parents
        parent::_beforeSave();
    }
}