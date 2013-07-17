<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Encryption
    extends Mage_Core_Model_Encryption
{

    /** Holds the mcrypt model */
    protected $_crypt   = null;

    /** Holds the crypt settings */
    protected $_data     = null;


    public function __construct()
    {
        $args = func_get_args();

        if (empty($args[0])) {
            $args[0] = array();
        }

        $this->_data = $args[0];
    }


    /**
     * Instantiate crypt model
     *
     * @param string $key
     * @return Varien_Crypt_Mcrypt
     */
    protected function _getCrypt($key = null)
    {
        if (is_null($this->_crypt)) {
            //Get the key from the config
            $key = $this->getPassword();

            //We need an encryption key
            if (empty($key)) {
                Mage::throwException('The paymentoperator encryption key is missing.');
            }

            //Decrypt the key with the magento default decrypter
            $key = Mage::helper('core')->getEncryptor()->decrypt($key);

            //Create a new mcrypt instance
            $this->_crypt = Varien_Crypt::factory()
                ->setCipher(MCRYPT_BLOWFISH)
                ->init($key);
        }

        return $this->_crypt;
    }


    /**
     * Encrypt a string.
     *
     * @param string $data
     * @return string
     */
    public function encrypt($data)
    {
        if (!is_string($data) || empty($data)) {
            Mage::throwException('The given data is not valid string.');
        }

        return bin2hex($this->_getCrypt()->encrypt($data));
    }


    /**
     * Decrypt a hex string string.
     *
     * @param string $data
     * @return string
     */
    public function decrypt($data)
    {
        return str_replace("\x0", '', trim($this->_getCrypt()->decrypt(pack('H*', $data))));
    }


    /**
     * Return true if we can use a hmac.
     *
     * @return boolean
     */
    public function hasHmac()
    {
        $data = $this->getHmac();
        return !empty($data);
    }


    /**
     * Creates the hmac for the given data.
     *
     * @param string $payId
     * @param string $transId
     * @param string $merchantId
     * @param string $amount
     * @param string $currency
     * @return string
     */
    public function createHmac($payId, $transId, $merchantId, $amount, $currency)
    {
        //check if we have an hmac
        if (!$this->hasHmac()) {
            Mage::throwException('No hmac salt is configured.');
        }

        //Get the hmac salt
        $key = Mage::helper('core')->getEncryptor()->decrypt(
            $this->getHmac()
        );

        //Create and return the hmac
        return hash_hmac(
            "sha256",
            "$payId*$transId*$merchantId*$amount*$currency",
            $key
        );
    }


    /**
     * Return the given hmac.
     *
     * @return string
     */
    public function getHmac()
    {
        return $this->_data['hmac'];
    }


    /**
     * Return the given password.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_data['password'];
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