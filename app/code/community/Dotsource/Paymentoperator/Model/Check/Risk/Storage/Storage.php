<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
{

    /** Holds the primary object */
    protected $_primaryObject       = null;

    /** Holds the primary save method */
    protected $_primarySaveMethod   = null;

    /** Holds the secondary object */
    protected $_secondaryObject     = null;

    /** Holds the secondary save method */
    protected $_secondarySaveMethod = null;

    /** Holds the storage handler */
    protected $_storageHandler      = null;


    /**
     * Check a storage object.
     */
    protected function _checkStorageObject($object)
    {
        //Check for object
        if (!$object instanceof Varien_Object) {
            throw new Exception(
                'The given object ' . get_class($object) . ' is not from type Varien_Object.'
            );
        }
    }


    /**
     * Add the primary object.
     *
     * @param object $object
     * @param string $saveMethod
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    public function setPrimaryObject($object, $saveMethod = null)
    {
        //Throws exception on error
        $this->_checkStorageObject($object);

        //Add the object
        $this->_primaryObject       = $object;
        $this->_primarySaveMethod   = $saveMethod;
        return $this;
    }


    /**
     * Add the secondary object.
     *
     * @param object $object
     * @param string $saveMethod
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    public function setSecondaryObject($object, $saveMethod = null)
    {
        //Throws exception on error
        $this->_checkStorageObject($object);

        //Add the object
        $this->_secondaryObject     = $object;
        $this->_secondarySaveMethod = $saveMethod;
        return $this;
    }


    /**
     * Set the given data to the storage objects.
     *
     * @param string $key
     * @param mixed $value
     * @param boolean $force
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    public function setData($key, $value)
    {
        //Transform the value
        $value = $this->getStorageHandler()->setTransform($value);

        //Set the data to the objects
        $this
            ->_setData($this->_getSecondaryObject(), $key, $value)
            ->_setData($this->_getPrimaryObject(), $key, $value);

        return $this;
    }


    /**
     * Set the given value in the given object.
     *
     * @param Varien_Object $object
     * @param string $key
     * @param mixed $value
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    protected function _setData($object, $key, $value)
    {
        //Check for a valid object
        if (!$object) {
            return $this;
        }

        //Get the current time and serialize the value
        $time               = $this->_getTime();
        $serializedValue    = serialize($value);

        //Check for successful serialize
        if ($value && !$serializedValue) {
            throw new Exception("Error by serialize value.");
        }

        //Set the new container and serialize the data
        $data = serialize(
            array(
                'time'      => $this->_getTime(),
                'payload'   => $serializedValue
            )
        );

        //Set the data
        $object->setData($key, $data);

        return $this;
    }


    /**
     * Return the data from the primary or secondary object.
     *
     * @param $key
     * @return mixed
     */
    public function getData($key)
    {
        //Get all data from the objects
        $primaryData    = $this->_getData($this->_getPrimaryObject(), $key);
        $secondaryData  = $this->_getData($this->_getSecondaryObject(), $key);
        $payload        = null;

        //Return the data if exists
        if ($primaryData && $secondaryData) {
            if ($primaryData['time'] >= $secondaryData['time']) {
                $payload = $primaryData['payload'];
            } else {
                $payload = $secondaryData['payload'];
            }
        } elseif ($primaryData) {
            $payload = $primaryData['payload'];
        } elseif ($secondaryData) {
            $payload = $secondaryData['payload'];
        }

        //Transform and return the payload data
        if (null !== $payload) {
            return $this->getStorageHandler()->getTransform($payload);
        }

        return null;
    }


    /**
     * Sync the data from the secondary object to the primary object.
     *
     * @param string $key
     * @param string $destinationSaveMethod
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    public function sync($key, $shouldSave = false)
    {
        //Get the container
        $primaryData    = $this->_getData($this->_getPrimaryObject(), $key);
        $secondaryData  = $this->_getData($this->_getSecondaryObject(), $key);
        $source         = null;
        $destination    = null;

        //Sync is only possible if at least one data available
        if ($primaryData && $secondaryData) {
            //Check if the data from the secondary object are newer
            if ($secondaryData['time'] > $primaryData['time']) {
                $source         = $this->_getSecondaryObject();
                $destination    = $this->_getPrimaryObject();
            }
        } elseif ($secondaryData && $this->_getPrimaryObject()) {
            $source         = $this->_getSecondaryObject();
            $destination    = $this->_getPrimaryObject();
        }

        //Check if we have an source and destination
        if ($source && $destination) {
            //Sync the data
            $this->_sync($source, $destination, $key);
        }

        //Check if we need to save the destination
        if ($shouldSave && $this->_getPrimarySaveMethod()) {
            call_user_func(array($this->_getPrimaryObject(), $this->_getPrimarySaveMethod()));
        }

        return $this;
    }


    /**
     * Set the data from the given source to the given destination object
     * with the given key.
     *
     * @param Varien_Object $source
     * @param Varien_Object $destination
     * @param string $key
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Storage
     */
    protected function _sync($source, $destination, $key)
    {
        $destination->setData($key, $source->getData($key));
        return $this;
    }


    /**
     * Get the data from the object.
     *
     * @param Varien_Object $object
     * @param string $key
     * @return array || null
     */
    protected function _getData($object, $key)
    {
        //Check for a valid object
        if (!$object) {
            return null;
        }

        //Get the data from the object
        $data = $object->getData($key);

        //Check for data
        if ($data) {
            $data =  @unserialize($data);

            //Normalize to null
            if (!$data || !is_array($data) || !isset($data['time'], $data['payload'])) {
                $data = null;
            } else {
                $data['payload'] = @unserialize($data['payload']);
            }
        } else {
            //Normalize to null
            $data = null;
        }

        return $data;
    }


    /**
     * Return the storage handler.
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Handler
     */
    public function getStorageHandler()
    {
        if (null === $this->_storageHandler) {
            $this->_storageHandler = Mage::getModel('paymentoperator/check_risk_storage_handler');
        }

        return $this->_storageHandler;
    }


    /**
     * Return the primary object.
     *
     * @return Varien_Object
     */
    protected function _getPrimaryObject()
    {
        return $this->_primaryObject;
    }


    /**
     * Return the primary save method name.
     *
     * @return string
     */
    protected function _getPrimarySaveMethod()
    {
        return $this->_primarySaveMethod;
    }


    /**
     * Return the secondary object.
     *
     * @return Varien_Object
     */
    protected function _getSecondaryObject()
    {
        return $this->_secondaryObject;
    }


    /**
     * Return the secondary save method name.
     *
     * @return string
     */
    protected function _getSecondarySaveMethod()
    {
        return $this->_secondarySaveMethod;
    }


    /**
     * Return true if the storage object has a primary object.
     *
     * @return boolean
     */
    public function hasPrimaryObject()
    {
        return null !== $this->_getPrimaryObject();
    }


    /**
     * Return true if the storage object has a secondary object.
     *
     * @return boolean
     */
    public function hasSecondaryObject()
    {
        return null !== $this->_getSecondaryObject();
    }


    /**
     * Return the current time value-
     *
     * @return int
     */
    protected function _getTime()
    {
        return time();
    }
}