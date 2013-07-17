<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Serialize
    implements Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Interface
{

    /**
	 * Unserialize the given string.
	 *
     * @param string $data
     * @return mixed
     */
    public function getData($data)
    {
        //Is empty or not a string?
        if (!$data || !is_string($data)) {
            return $data;
        }

        return @unserialize($data);
    }


    /**
	 *
	 * Serialize the given data to string.
	 *
     * @param mixed $data
     * @return string
     */
    public function setData($data)
    {
        $serializedData = @serialize($data);

        if (!$serializedData) {
            Mage::throwException('The serialized data are empty.');
        }

        return $serializedData;
    }
}