<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Config
    extends Mage_Core_Helper_Abstract
{

    /** Holds the specific payment merchant ids */
    const CONNECTION_PAYMENT_MERCHANT_PATH              = 'paymentoperator_account/%s';

    /** Holds default payment settings */
    const CONNECTION_SETTINGS_DEFAULT_PATH              = 'paymentoperator_account/default';

    /** Holds the payment settings */
    const PAYMENT_SETTINGS_PATH                         = 'payment/%s';

    /** Holds the payment specific merchant information with the currency as access keys */
    protected $_additionalCurrencyMerchantSettings      = null;

    /** Holds the payment specific merchant information with the merchant id as access keys */
    protected $_additionalMerchantIdMerchantSettings    = null;


    public function __construct()
    {
        $this->_additionalCurrencyMerchantSettings      = new Varien_Object();
        $this->_additionalMerchantIdMerchantSettings    = new Varien_Object();
    }


    /**
     * Return a specific merchant account for the given payment. if no
     * data available the function return the default value.
     *
     * @param string $payment
     * @param string $country
     * @param string $field
     * @return string
     */
    public function getPaymentSpecificMerchantFieldByCurrency($payment, $currency, $field = null)
    {
        //Keys are in lower case
        $currency   = strtolower($currency);
        $field      = strtolower($field);

        //Build the path to the data
        $path       = "$payment/$currency";

        //Check for return the complete settings or a field
        if (!empty($field)) {
            $path .= "/$field";
        }

        //Try to load the merchant data from payment
        $this->_loadSpecificMerchantSettingsFromPayment($payment);

        //Get the data from the path
        $data = $this->_getAdditionalMerchantSettingsByCurrency()->getData($path);

        //Check if we have data
        if (!empty($data)) {
            return $data;
        }

        return $this->getDefaultSettings($field);
    }


    /**
     * Return a specific merchant account for the given payment. if no
     * data available the function return the default value.
     *
     * @param string $payment
     * @param string $country
     * @param string $field
     * @return string
     */
    public function getPaymentSpecificMerchantFieldByMerchantId($payment, $merchantId, $field = null)
    {
        //Keys are in lower case
        $merchantId = strtolower($merchantId);
        $field      = strtolower($field);

        //Build the path to the data
        $path       = "$payment/$merchantId";

        //Check for return the complete settings or a field
        if (!empty($field)) {
            $path .= "/$field";
        }

        //Try to load the merchant data from payment
        $this->_loadSpecificMerchantSettingsFromPayment($payment);

        //Get the data from the path
        $data = $this->_getAdditionalMerchantSettingsByMerchantId()->getData($path);

        //Check if we have data
        if (!empty($data)) {
            return $data;
        }

        return $this->getDefaultSettings($field);
    }


    /**
     * Return the default settings for an given field.
     *
     * @param string $field
     * @return string
     */
    public function getDefaultSettings($field = null)
    {
        //Return all default value if no field is given
        if (empty($field)) {
            return array(
                'id'        => $this->getMerchantId(),
                'password'  => $this->getEncryptionPassword() ,
                'hmac'      => $this->getHmac(),
            );
        }

        //Return the default value
        switch ($field) {
            case 'id':
                return $this->getMerchantId();
            case 'hmac':
                return $this->getHmac();
            case 'password':
                return $this->getEncryptionPassword();
        }

        Mage::throwException("No default value for the field \"$field\" available");
    }


    /**
     * Return the encryption password.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return Mage::getStoreConfig(self::CONNECTION_SETTINGS_DEFAULT_PATH.'/merchant_id');
    }


    /**
     * Return the encryption password.
     *
     * @return string
     */
    public function getEncryptionPassword()
    {
        return Mage::getStoreConfig(self::CONNECTION_SETTINGS_DEFAULT_PATH.'/encryption_password');
    }


    /**
     * Return the hmac salt.
     *
     * @return string
     */
    public function getHmac()
    {
        return Mage::getStoreConfig(self::CONNECTION_SETTINGS_DEFAULT_PATH.'/hmac_salt');
    }


    /**
     * Return the base url.
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return Mage::getStoreConfig(self::CONNECTION_SETTINGS_DEFAULT_PATH.'/baseurl');
    }


    /**
     * Return the field from the given payment code.
     *
     * @param string $payment
     * @param string $field
     * @return string
     */
    public function getPaymentField($payment, $field)
    {
        return Mage::getStoreConfig(sprintf(self::PAYMENT_SETTINGS_PATH, $payment).'/'.$field);
    }


    /**
     * Load the stored merchant accounts from the given payment.
     *
     * @param string $payment
     */
    protected function _loadSpecificMerchantSettingsFromPayment($payment)
    {
        //If the key already exists we don't need to do anything
        if (array_key_exists($payment, $this->_getAdditionalMerchantSettingsByCurrency())) {
            return;
        }

        //Get the payment merchant accounts
        $merchantAccounts = Mage::getStoreConfig(
            sprintf(self::CONNECTION_PAYMENT_MERCHANT_PATH, $payment).
            '/merchant_account_info'
        );

        //Check the result for data
        if (empty($merchantAccounts) || !is_string($merchantAccounts)) {
            $this->_getAdditionalMerchantSettingsByCurrency()->setData($payment, array());
            $this->_getAdditionalMerchantSettingsByMerchantId()->setData($payment, array());
            return;
        }

        //Suppress any output by unserializing
        $merchantAccounts    = @unserialize($merchantAccounts);

        //Recheck the result
        if (empty($merchantAccounts) || !is_array($merchantAccounts)) {
            $this->_getAdditionalMerchantSettingsByCurrency()->setData($payment, array());
            $this->_getAdditionalMerchantSettingsByMerchantId()->setData($payment, array());
            return;
        }

        //Map the currency as main key for the settings
        $currencyMappedMerchantAccounts = $this->_getMappedSpecificAccountData('currency', $merchantAccounts);

        //Map the id as main key for the settings
        $idMappedMerchantAccounts       = $this->_getMappedSpecificAccountData('id', $merchantAccounts);

        //Add the data to the mapped array
        $this->_getAdditionalMerchantSettingsByCurrency()->setData($payment, $currencyMappedMerchantAccounts);
        $this->_getAdditionalMerchantSettingsByMerchantId()->setData($payment, $idMappedMerchantAccounts);
    }


    /**
     * Convert the given 2dim array with the call:
     *
     * $this->_getMappedSpecificAccountData('optionkey', array(...));
     *
     * From:
     * Array
     * (
     *   [_1258715461606_606] => Array
     *   (
     *      [optionkey] => DE
     *      [id] => DE_id
     *      [code] => DE_code
     *   )
     *   [_1258716196164_164] => Array
     *   (
     *      [optionkey] => US
     *      [id] => US_id
     *      [code] => Us_code
     *   )
     * )
     *
     * To:
     * Array
     * (
     *  [de] => Array
     *  (
     *      [optionkey] => DE
     *      [id] => DE_id
     *      [code] => DE_code
     *   )
     *   [us] => Array
     *   (
     *      [optionkey] => US
     *      [id] => US_id
     *      [code] => Us_code
     *   )
     * )
     *
     * by the given key.
     *
     * @param string $key
     * @param array $array
     * @return array
     */
    protected function _getMappedSpecificAccountData($key, array $array)
    {
        $newArray = array();

        //Map the value from the key as main key
        foreach ($array as $data) {
            if (is_array($data) && array_key_exists($key, $data)) {
                //Default is case sensitive
                $value = strtolower($data[$key]);

                //Set the value as key with the information
                $newArray[$value] = array_change_key_case($data, CASE_LOWER);
            }
        }

        return $newArray;
    }


    /**
     * Return mapped data.
     *
     * @return Varien_Object
     */
    protected function _getAdditionalMerchantSettingsByCurrency()
    {
        return $this->_additionalCurrencyMerchantSettings;
    }


    /**
     * Return mapped data.
     *
     * @return Varien_Object
     */
    protected function _getAdditionalMerchantSettingsByMerchantId()
    {
        return $this->_additionalMerchantIdMerchantSettings;
    }
}