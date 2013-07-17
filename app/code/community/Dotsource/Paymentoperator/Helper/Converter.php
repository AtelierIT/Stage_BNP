<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Converter
    extends Mage_Core_Helper_Abstract
{

    /** Converts the given payment to magento */
    const MAGENTO_TYPE      = 1;

    /** Converts the given type to paymentoperator */
    const PAYMENTOPERATOR_TYPE     = 2;

    /** Holds the types of the [paymentoperator => magento] types */
    protected $_ccTypeMap   = array(
        'VISA'          => 'VI',
        'MasterCard'    => 'MC',
        'AMEX'          => 'AE',
        'SOLO'          => 'SS',
        'DINERS'        => '',
        'JCB'           => 'JCB',
        'CBN'           => '',
        'SWITCH'        => 'SM',
        'Maestro'       => 'SM',
        'Dankort'       => '',
    );


    /**
     * Return an amount to an paymentoperator specific amount.
     *
     * @param   mixed   $amount
     * @param   string  $currencyCode
     * @return  integer
     */
    public function formatPrice($amount, $currencyCode)
    {
        //Don't use decimal digest for YEN
        if ('JPY' === strtoupper($currencyCode)) {
            $amount = floor($amount);
        } else {
            $amount = number_format($amount, 2, '', '');
        }

        return (int) $amount;
    }

    /**
     * Return the given paymentoperator amount (cent) to an normal price.
     *
     * @param   mixed   $amount
     * @param   string  $currencyCode
     * @return  string
     */
    public function revertFormatPrice($amount, $currencyCode)
    {
        //Don't use decimal digest for YEN
        if ('JPY' === strtoupper($currencyCode)) {
            $amount = (int) floor($amount);;
        } else {
            $amount = number_format($amount/100.0, 2, '.', '');
        }

        return (string) $amount;
    }

    /**
     * Return the paymentoperator expire date.
     *
     * @param   int     $month
     * @param   int     $year
     * @return  string
     */
    public function getPaymentoperatorExpireDate($month, $year)
    {
        return sprintf('%d%02d', $year, $month);
    }

    /**
     * Explodes the given name into first and lastname and add it to the
     * also given adsress.
     *
     * @param   Mage_Customer_Model_Address_Abstract    $address
     * @param   string                                  $name
     * @return  Dotsource_Paymentoperator_Helper_Converter
     */
    public function setNameToAddress(Mage_Customer_Model_Address_Abstract $address, $name)
    {
        //Split the full name into first- and lastname
        $names = explode(' ', $name, 2);
        $address->setFirstname($names[0]);
        if (isset($names[1])) {
            $address->setLastname($names[1]);
        }
        return $this;
    }


    /**
     * Convert the given string to an latin string.
     * If the string can't convert the given string will
     * return.
     *
     * @param string $string
     */
    public function convertToLatin($string)
    {
        //Check if we need to convert the string
        if ("UTF-8" == mb_detect_encoding($string, "UTF-8")
            && mb_check_encoding($string, "UTF-8")
        ) {
            //Convert the string
            $tmp = @iconv('UTF-8', 'ISO-8859-1', $string);

            //Check if the conversion are successfully
            if (!empty($string) && !empty($tmp)) {
                return $tmp;
            }
        }

        //Fallback
        return $string;
    }


    /**
     * Convert the given string to an utf8 string.
     * If the string can't convert the given string will
     * return.
     *
     * @param string $string
     */
    public function convertToUtf8($string)
    {
        //Check if we need to convert the string
        if ("UTF-8" != mb_detect_encoding($string, "UTF-8")
            || !mb_check_encoding($string, "UTF-8")
        ) {
            //Convert the string
            $tmp = @iconv('ISO-8859-1', 'UTF-8', $string);

            //Check if the conversion are successfully
            if (!empty($string) && !empty($tmp)) {
                return $tmp;
            }
        }

        //Fallback
        return $string;
    }


    /**
     * Convert a boolean to an integer.
     * true     = 1
     * false    = 0
     *
     * @param boolean $boolean
     * @return int
     */
    public function convertBooleanToInt($boolean)
    {
        //Check for boolean
        if (!is_bool($boolean)) {
            Mage::throwException('No boolean given.');
        }

        if ($boolean) {
            return 1;
        }

        return 0;
    }


    /**
     * Strip all dangerous tags from the content.
     *
     * @param array $tags
     * @param Dotsource_Paymentoperator_Object || array $content
     */
    public function stripDangerousTags($content, array $tags)
    {
        //Holds the data keys
        $contentKeys = null;

        //Parse the data keys
        if (is_array($content)) {
            $contentKeys = array_keys($content);
        } elseif ($content instanceof Varien_Object) {
            $data = $content->getData();
            $contentKeys = array_keys($data);
        }

        //If something empty what we need to process the
        if (empty($contentKeys) || empty($tags)) {
            return $content;
        }

        //Change the key case
        $tags = array_flip(array_change_key_case(array_flip(array_values($tags)), CASE_LOWER));
        $tags = array_unique($tags);

        //We use an in case sensitive search for replace the information
        foreach ($contentKeys as $indexKey) {
            if (in_array(strtolower($indexKey), $tags)) {
                $content[$indexKey] = '*';
            }
        }

        return $content;
    }


    /**
     * Return the converted cc type.
     *
     * @param string $ccType
     * @param int $returnType
     */
    public function getCcType($ccType, $returnType = self::MAGENTO_TYPE)
    {
        //Check for already right type is given
        if (self::MAGENTO_TYPE === $returnType) {
            if ($this->isMagentoCcType($ccType)) {
                return strtoupper($ccType);
            }
        } elseif (self::PAYMENTOPERATOR_TYPE === $returnType) {
            if ($this->isPaymentoperatorCcType($ccType)) {
                return $ccType;
            }
        } else {
            throw new Exception("Wrong return type are given.");
        }

        //Get the converted type
        $ccTypeUpper = strtoupper($ccType);
        foreach ($this->_ccTypeMap as $paymentoperatorCc => $magentoCc) {
            if ($ccTypeUpper === strtoupper($paymentoperatorCc)
                || $ccTypeUpper === strtoupper($magentoCc)
            ) {
                if (self::MAGENTO_TYPE === $returnType) {
                    if ($magentoCc) {
                        return $magentoCc;
                    } else {
                        return 'OT';
                    }
                } elseif ($paymentoperatorCc && self::PAYMENTOPERATOR_TYPE === $returnType) {
                    return $paymentoperatorCc;
                }
            }
        }

        return $ccType;
    }


    /**
     * Check if the given cc type is in the magento format.
     *
     * @param string $type
     * @return boolean
     */
    public function isMagentoCcType($type)
    {
        $magentoCcTypes = Mage::getSingleton('payment/config')->getCcTypes();
        array_change_key_case($magentoCcTypes, CASE_UPPER);
        return isset($magentoCcTypes[strtoupper($type)]);
    }


    /**
     * Check if the given type is a paymentoperator cc type.
     *
     * @param string $type
     * @return boolean
     */
    public function isPaymentoperatorCcType($type)
    {
        //Copy the array
        $paymentoperatorCcTypes = $this->_ccTypeMap;
        array_change_key_case($paymentoperatorCcTypes, CASE_UPPER);
        return isset($paymentoperatorCcTypes[strtoupper($type)]);
    }


    /**
     * Return the given id as transaction code.
     *
     * @param int $id
     * @return string
     */
    public function formatAsTransactionCode($id)
    {
        return (string) sprintf("%09s", $id);
    }


    /**
     * Return the quotes items description.
     *
     * @param Mage_Sales_Model_Order || Mage_Sales_Model_Quote $model
     * @return array
     */
    public function convertOrderToInformationOrderDesc($model, $maxItems = 10)
    {
        /* @var $orcale Dotsource_Paymentoperator_Model_Oracle_Type_Order */
        $orcale         = Mage::getModel('paymentoperator/oracle_type_order')->setModel($model);
        $baseCurrency   = $orcale->getBaseCurrencyCode();
        $qtyKey         = '';

        //Holds the converted items
        $orderDesc      = array();
        $startValue     = 2;

        //Get the qty access key
        if ($orcale->isOrder()) {
            $qtyKey = 'qty_ordered';
        } else {
            $qtyKey = 'qty';
        }

        /* @var $item Mage_Sales_Model_Order_Item */
        foreach ($model->getAllVisibleItems() as $item) {
            //Return a empty array if we have more items than the max value
            if (--$maxItems < 0) {
                return array();
            }

            //USe the base price
            $basePriceInclTax = 0;
            if ($item->getBasePriceInclTax()) {
                $basePriceInclTax = $item->getBasePriceInclTax();
            } else {
                $basePriceInclTax = Mage::helper('checkout')->getBasePriceInclTax($item);
            }

            //Create a array with all parts
            $parts = array(
                str_replace(',', '-', $item->getName()),
                $this->formatPrice($basePriceInclTax, $baseCurrency),
                str_replace(',', '-', $item->getSku()),
                $item->getData($qtyKey),
            );

            //Create the order desc string
            $orderDesc[++$startValue] = implode(',', $parts);
        }

        //Check if we have a shipping amount
        if ($orcale->getBaseShippingAmount()
            && !$this->_getHelper()->getPaymentHelper()->isZeroAmount($orcale->getBaseShippingAmount())
        ) {
            //Check if we can add the shipping position
            if (--$maxItems < 0) {
                return array();
            }

            $orderDesc[++$startValue] = "{$this->__('Shipping costs')},{$this->formatPrice($orcale->getBaseShippingAmount(), $baseCurrency)},,1";
        }

        return $orderDesc;
    }

    /**
     * Try to return the name from the given address.
     *
     * @param $address
     * @return string || null
     */
    protected function _getStreetName($address)
    {
        try {
            //Split on the first number
            preg_match("/\D+/", $address, $street);

            if (!empty($street)) {
                return trim($street[0]);
            }
        } catch(Exception $e) {
        }

        return null;
    }


    /**
     * Try to return the number from the given address.
     *
     * @param $address
     * @return string || null
     */
    protected function _getStreetNumber($address)
    {
        try {
            //Try to parse the number from the string
            preg_match("/\d+\w*/", $address, $number);

            if (!empty($number)) {
                return trim($number[0]);
            }
        } catch(Exception $e) {
        }

        return null;
    }


    /**
     * Try to split the street in name and number from the given address object.
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param int $useStreet    Which street should use for the split.
     *                              - 0 / null should use the full street name
     *                              - 1 address line 1
     *                              - ...
     *                              - 4 address line 4
     *
     * @return Varien_Object
     */
    public function splitStreet(Mage_Customer_Model_Address_Abstract $address, $useStreet = 1)
    {
        //This one holds the result
        $result = new Varien_Object();

        //Try to get the result through the event
        Mage::dispatchEvent(
            'paymentoperator_split_street',
            array(
                'address'       => $address,
                'use_street'    => $useStreet,
                'result'        => $result,
            )
        );

        //Return the result with the data
        if ($result->hasData()) {
            return $result;
        }

        //Data we need
        $streetFull = null;

        //Check witch street we should use
        if ($useStreet && is_numeric($useStreet)) {
            $streetFull = $address->getStreet($useStreet);
        } else {
            $streetFull = $this->getFullStreet($address);
        }

        $streetName     = null;
        $streetNumber   = null;

        //Try to separate the street name and the street number
        try {
            $streetName     = $this->_getStreetName($streetFull);
            $streetNumber   = $this->_getStreetNumber($streetFull);

            //If a part is missing fallback
            if (empty($streetName) || empty($streetNumber)) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $streetName     = $streetFull;
            $streetNumber   = "";
        }

        //Street name is never empty
        $result->setStreetName($streetName);

        //If the number is not empty add to the result
        if (!empty($streetNumber)) {
            $result->setStreetNumber($streetNumber);
        }

        return $result;
    }


    /**
     * Return the full street as string.
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return string
     */
    public function getFullStreet(Mage_Customer_Model_Address_Abstract $address)
    {
        //Get the full street
        $streetFull = $address->getStreetFull();

        //Join the street data if we have more than one line
        if (is_array($streetFull)) {
            $streetFull = implode(' ', $streetFull);
        }

        return $streetFull;
    }

    /**
     * Remove all whitespaces from the given string.
     *
     * @param   string  $data
     * @return  string
     */
    public function removeWhitespaces($data)
    {
        return preg_replace('/\s+/', '', $data);
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