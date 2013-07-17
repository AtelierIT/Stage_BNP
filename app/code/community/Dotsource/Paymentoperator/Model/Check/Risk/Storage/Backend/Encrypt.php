<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Encrypt
    implements Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Interface
{

    /**
     * Decrypt the given string.
     *
     * @param string $data
     * @return string
     */
    public function getData($data)
    {
        //Check the given data
        if (!is_string($data)) {
            Mage::throwException('Only strings can decrypted.');
        } elseif (!$data) {
            return $data;
        }

        return Mage::helper('core')->decrypt($data);
    }


    /**
     * Encrypt the given data to string.
     *
     * @param mixed $data
     * @return string
     */
    public function setData($data)
    {
        //Check the given data
        if (!is_string($data)) {
            Mage::throwException('Only strings can encrypted.');
        } elseif (!$data) {
            return $data;
        }

        return Mage::helper('core')->encrypt($data);
    }
}