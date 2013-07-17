<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Object
    extends Varien_Object
{

    /** Holds is the data getting encrypted */
    protected $_encryption              = false;

    /** Holds the encryption settings */
    protected $_encryptionSettings      = array();

    /** Holds the encryption model */
    protected $_encryptionModel         = null;

    /** Holds keys that values should not decode */
    protected $_useUndecodeParameters   = array();


    /**
     * Check if id field is setup in the data array.
     * If id field name is setup the id fields name is used.
     *
     * @return unknown
     */
    public function hasId()
    {
        $id = $this->getIdFieldName();

        //check if field id name is setup
        if (empty($id)) {
            return $this->hasData('id');
        } else {
            return $this->hasData($id);
        }
    }

    /**
     * Return true if the value from the given key is empty.
     *
     * @param string || null $key
     * @return boolean
     */
    public function isDataEmpty($key = null)
    {
        if (is_null($key)) {
            return $this->isEmpty();
        }

        //Get the value from the key
        $data = $this->getData($key);

        //Return the result
        return empty($data);
    }

    /**
     * Set/Get attribute wrapper
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    public function __call($method, $args)
    {
        try {
            return parent::__call($method, $args);
        } catch(Exception $e) {
            switch (substr($method, 0, 5)) {
                case 'empty' :
                    $key = $this->_underscore(substr($method, 5));
                    return isset($this->_data[$key]) && empty($this->_data[$key]);
            }

            //throw the old exception
            throw $e;
        }
    }

    /**
     * @see Varien_Object::toString()
     *
     * @param string $noNeed
     * @return string
     */
    public function toString($noNeed = null)
    {
        //Get the string
        $data = $this->_getString();

        //Encrypt the data
        if ($this->getEncryption()) {
            $data = $this->getEncryptor()->encrypt($data);
        }

        return $data;
    }

    /**
     * Return the length of the given string in the container.
     *
     * @return int
     */
    public function getStringLength()
    {
        return strlen($this->_getString());
    }

    /**
     * Applies charset convertion to the entries of parents::toArray() method.
     *
     * @param array $arrAttributes
     * @return array
     */
    public function toArray(array $arrAttributes = array())
    {
        $data = parent::toArray($arrAttributes);
        $outData = array();
        foreach ($data as $key => $value) {
            if (is_string($key) && !is_array($value) && !is_object($value)) {
                $key    = $this->_getHelper()->getConverter()->convertToLatin($key);
                $value  = $this->_getHelper()->getConverter()->convertToLatin($value);
                $outData[$key] = $value;
            } else {
                //Put alos the array or object values in the array
                $outData[$key] = $value;
            }
        }
        return $outData;
    }

    /**
     * Return the string of the data container.
     *
     * @return string
     */
    protected function _getString()
    {
        $data = array();

        foreach ($this->toArray() as $key => $value) {
            if (is_string($key) && !is_array($value) && !is_object($value)) {
                $data[] = "$key=$value";
            }
        }

        //Return as string
        return implode('&', $data);
    }

    /**
     * Parse the given query string and regard the unencoded parameters.
     *
     * @param   string  $query
     * @return  array
     */
    protected function _parseQueryString($query)
    {
        //First parse the string
        parse_str($query, $result);
        $result = array_change_key_case($result, CASE_LOWER);

        //Use the parameters that should use as an unencoded string
        if ($this->getUseUndecodeParameters()) {
            foreach (explode('&', $query) as $queryPart) {
                //Parse the data
                list($name, $value) = explode('=', $queryPart, 2);

                //Normalize the name and check if we need the key unencoded
                $name = strtolower($name);
                if (in_array($name, $this->getUseUndecodeParameters())) {
                    $result[$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @see Varien_Object::setData()
     *
     * @param   string|array    $key
     * @param   mixed           $value
     * @return  Varien_Object
     */
    public function setData($key, $value = null)
    {
        //check if we need to parse the string
        if ($key && is_string($key) && null === $value) {
            //Parse the string
            $responseArray  = $this->_parseQueryString($key);
            $result         = array();

            //Convert to uft8
            foreach ($responseArray as $key => $value) {
                //Convert
                $key            = $this->_getConverter()->convertToUtf8($key);
                $value          = $this->_getConverter()->convertToUtf8($value);

                //Add as result
                $result[$key]   = $value;
            }

            //Set the data
            $key = $result;
        }

        //Do the parent stuff
        return parent::setData($key, $value);
    }

    /**
     * Return the value who is saved under the given key or the default value
     * will returned is the key not exists.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDataOrDefault($key, $default)
    {
        //Check if the key exists
        if ($this->hasData($key)) {
            return $this->getData($key);
        }

        return $default;
    }

    /**
     * Turn the encryption mode on or off.
     *
     * @param boolean $_encryption
     * @return Dotsource_Paymentoperator_Object
     */
    public function setEncryption($encryption)
    {
        $this->_encryption = $encryption;
        return $this;
    }

    /**
     * Return if the data need to encrypt.
     *
     * @return boolean
     */
    public function getEncryption()
    {
        return $this->_encryption;
    }

    /**
     * Set the already exist encryption model.
     *
     * @param Dotsource_Paymentoperator_Model_Encryption $encryptionModel
     * @return Varien_Object
     */
    public function setEncryptor(Dotsource_Paymentoperator_Model_Encryption $encryptorModel)
    {
        $this->_encryptionModel = $encryptorModel;
        return $this;
    }

    /**
     * Return the encryption model.
     *
     * @return Dotsource_Paymentoperator_Model_Encryption
     */
    public function getEncryptor()
    {
        if (!$this->hasEncryptor()) {
            //Create the new encryption model
            $this->_encryptionModel = Mage::getModel(
                'paymentoperator/encryption',
                $this->getEncryptionSettings()
            );
        }

        return $this->_encryptionModel;
    }

    /**
     * Return true if an encryption model is available.
     *
     * @return boolean
     */
    public function hasEncryptor()
    {
        return null !== $this->_encryptionModel;
    }

    /**
     * Set the encryption model settings. You can only set the settings
     * if the encryption model not exists.
     *
     * @param array $settings
     * @return Dotsource_Paymentoperator_Object
     */
    public function setEncryptionSettings(array $settings)
    {
        //Settings only available if no encryption model exists
        if ($this->hasEncryptor()) {
            Mage::throwException('You can\'t change the encryption settings the encryption model already exist. ');
        }

        //Set the settings
        $this->_encryptionSettings = $settings;
        return $this;
    }

    /**
     * Return the encryption settings.
     *
     * @return array
     */
    public function getEncryptionSettings()
    {
        return $this->_encryptionSettings;
    }

    /**
     * Return true if encryption settings are available.
     *
     * @return boolean
     */
    public function hasEncryptionSettings()
    {
        return !empty($this->_encryptionSettings);
    }

    /**
     * Set the unencoded parameters.
     *
     * @param   array   $parameters
     * @return  Dotsource_Paymentoperator_Object
     */
    public function setUseUndecodeParameters(array $parameters)
    {
        $this->_useUndecodeParameters = $parameters;
        return $this;
    }

    /**
     * Return the unencoded parameters.
     *
     * @return array
     */
    public function getUseUndecodeParameters()
    {
        return $this->_useUndecodeParameters;
    }

    /**
     * Return the paymentoperator converter.
     *
     * @return Dotsource_Paymentoperator_Helper_Converter
     */
    protected function _getConverter()
    {
        return Mage::helper('paymentoperator/converter');
    }

    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}