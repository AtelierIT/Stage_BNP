<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Paymentinformation
    extends Varien_Object
{

    /**
     * Holds all payment key features
     */
    const ENCRYPTED     = 1; //Data is encrypted
    const PREFILL       = 2; //Try to load the data from a previous order
    const REQUIRED      = 4; //These data must available after load
    const PAYMENT_KEY   = 8; //Used to create a unique payment key
    const REMOVE        = 16; //Remove the payment data before save
    const ALL           = PHP_INT_MAX;


    /**
     * Holds already loaded payment informations.
     *
     * @var array
     */
    protected static $_paymentInformationCache  = array();

    /**
     * Holds already loaded payment group keys.
     *
     * @var array
     */
    protected static $_paymentKeyGroups         = array();


    /**
     * Holds the cache key for the given payment model
     *
     * @var string
     */
    protected $_cacheKey                        = null;

    /**
     * Holds the payment method.
     *
     * @var Dotsource_Paymentoperator_Model_Payment_Abstract
     */
    protected $_paymentMethod                   = null;

    /**
     * Holds the customer model.
     *
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer                        = null;


    /**
     * Returns the Varien_Object with the payment fields.
     *
     * @return Varien_Object
     */
    public function getPaymentInformation()
    {
        //Customer available?
        $customer = $this->_getCustomer();
        if (!$customer || !$customer->getId()) {
            return new Varien_Object();
        }

        //Check for a cache entry first
        $cacheKey   = $this->_getCacheKey();
        $result     = $this->_getPaymentCacheEntry($cacheKey);
        if ($result) {
            return $result;
        }

        //Set the empty varien object as new cache entry
        $result = new Varien_Object();
        $this->_setPaymentCacheEntry($cacheKey, $result);


        /* @var $orderResourceModel Mage_Sales_Model_Mysql4_Order_Collection */
        //Setup the default data
        $orderResourceModel = Mage::getResourceModel('sales/order_collection');
        $orderSelect        = $orderResourceModel->getSelect();
        $orderResourceModel
            ->addAttributeToFilter('customer_id', $this->_getCustomer()->getId())
            ->addFieldToFilter(
                'status',
                array(
                    'nin' => array(
                        Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE,
                        Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_AUTHORIZATION,
                    )
                )
            )
            ->addFieldToFilter(
                'state',
                array(
                    'nin' => array(
                        Mage_Sales_Model_Order::STATE_CANCELED,
                        Mage_Sales_Model_Order::STATE_HOLDED,
                        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                        Mage_Sales_Model_Order::STATE_NEW
                    )
                )
            );

        //Reset all columns and set the sql limit to 1 row
        $orderSelect
            ->reset(Zend_Db_Select::COLUMNS)
            ->limit(1);

        //Add the rest of the sql statement
        if ($this->_getHelper()->getFeatureHelper()->hasFlatTables()) {
            $this->_getLastPaymentInformationSqlForFlatTables($orderResourceModel);
        } else {
            $this->_getLastPaymentInformationSqlForEav($orderResourceModel);
        }

        //Get the result and check if the data are valid
        $paymentData = $orderResourceModel->getResource()->getReadConnection()->fetchRow($orderSelect);
        if ($paymentData && is_array($paymentData)) {
            //Check if we have all required data
            $requiredFields = $this->getFeaturedPaymentFields(self::REQUIRED);
            $usePaymentData = true;
            foreach ($requiredFields as $field) {
                if (empty($paymentData[$field])) {
                    $usePaymentData = false;
                    break;
                }
            }

            //Add if the data are valid
            if ($usePaymentData) {
                $result->setData($paymentData);
            }
        }

        //Set the selected fields
        return $result;
    }

    /**
     * Return the customer model.
     *
     * @return Mage_Customer_Model_Customer|false
     */
    protected function _getCustomer()
    {
        if (null === $this->_customer) {
            if ($this->getCustomer()) {
                $this->_customer = $this->getCustomer();
            } else {
                $this->_customer = $this->_getHelper()->getCustomer();
            }

            //If we don't have any customer model use false
            if (!$this->_customer) {
                $this->_customer = false;
            }
        }

        return $this->_customer;
    }

    /**
     * Join the flat tables.
     *
     * @param Mage_Sales_Model_Mysql4_Order_Collection $orderResourceModel
     */
    public function _getLastPaymentInformationSqlForFlatTables(
        /*Mage_Sales_Model_Mysql4_Order_Collection*/ $orderResourceModel)
    {
        //Get the select object
        $select = $orderResourceModel->getSelect();

        //Join the payment information
        $select
            ->joinLeft(
                array('payment' => $orderResourceModel->getTable('sales/order_payment')),
                'payment.parent_id=main_table.entity_id',
                $this->getFeaturedPaymentFields(self::PREFILL)
            )
            ->where('payment.method=?', $this->_getPaymentMethod()->getCode())
            ->order('main_table.created_at DESC');
    }

    /**
     * Join the eav attributes.
     *
     * @param Mage_Sales_Model_Mysql4_Order_Collection $orderResourceModel
     */
    public function _getLastPaymentInformationSqlForEav(
        /*Mage_Sales_Model_Mysql4_Order_Collection*/ $orderResourceModel)
    {
        /* @var $attributeModel Mage_Eav_Model_Config */
        $attributeModel         = Mage::getSingleton('eav/config');
        $paymentMethodAttribute = $attributeModel->getAttribute('order_payment', 'method');
        $select                 = $orderResourceModel->getSelect();

        //Join the payment id and the payment method
        $select->joinLeft(
            array('payment' => $orderResourceModel->getTable('sales/order_entity')),
            'payment.parent_id=e.entity_id AND '
            . 'payment.entity_type_id=' . $paymentMethodAttribute->getEntityTypeId(),
            array(/*'payment_id' => 'value'*/)
        )
        ->joinInner(
            array('_table_payment_method' => $paymentMethodAttribute->getBackendTable()),
            'payment.entity_id=_table_payment_method.entity_id AND '
            . '_table_payment_method.attribute_id=' . $paymentMethodAttribute->getId(),
            array(/*'payment_method' => 'value'*/)
        );

        //Join the payment fields
        foreach ($this->getFeaturedPaymentFields(self::PREFILL) as $field) {
            $attribute = $attributeModel->getAttribute('order_payment', $field);

            if ($attribute->hasData() && $attribute->getId()) {
                //Build a table name
                $tableName = "_table_$field";

                //Join the attribute
                $select
                    ->joinInner(
                        array($tableName => $attribute->getBackendTable()),
                        "payment.entity_id=$tableName.entity_id AND $tableName.attribute_id={$attribute->getId()}",
                        array($field => 'value')
                    );
            } else {
                Mage::throwException("Can't find the attribute \"$field\" of the \"order_payment\" entity.");
            }
        }

        //Only get the last payment information
        $select
            ->order('e.created_at DESC')
            ->where('_table_payment_method.value=?', $this->_getPaymentMethod()->getCode());
    }

    /**
     * Setup the payment method.
     *
     * @param $paymentMethod
     * @return Dotsource_Paymentoperator_Model_Paymentinformation
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->_paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * Return the payment method.
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Abstract
     */
    protected function _getPaymentMethod()
    {
        return $this->_paymentMethod;
    }

    /**
     * Return the cache key.
     *
     * @return string
     */
    protected function _getCacheKey()
    {
        if (null === $this->_cacheKey) {
            //Holds the field informations
            $fieldInformations  = $this->getFeaturedPaymentFields();
            $cacheKeyParts      = array($this->_getCustomer()->getId(), $this->_getPaymentMethod()->getCode());

            //Build the cache key
            foreach ($fieldInformations as $options) {
                $cacheKeyParts[] = $options;
            }

            $this->_cacheKey = implode('#', $cacheKeyParts);
        }

        return $this->_cacheKey;
    }

    /**
     * Return a cache entry. If no entry exists under the key the
     * method return null.
     *
     * @param string $key
     * @return Varien_Object|null
     */
    protected function _getPaymentCacheEntry($key)
    {
        if (isset(self::$_paymentInformationCache[$key])) {
            return self::$_paymentInformationCache[$key];
        }

        return null;
    }

    /**
     * Set a payment information entry to the cache.
     *
     * @param string $key
     * @param Varien_Object|null $value
     */
    protected function _setPaymentCacheEntry($key, $value)
    {
        self::$_paymentInformationCache[$key] = $value;
        return $this;
    }

    /**
     * Return the all payment fields that support the given feature(s).
     *
     * @param   int|null  $feature
     * @return  array
     */
    public function getFeaturedPaymentFields($feature = null)
    {
        //Return all payment informations
        if (null === $feature) {
            return array_keys($this->_getPaymentMethod()->getAllPaymentInformationFields());
        }

        //Get a specified peyment field list
        $cacheKey = $this->_getPaymentMethod()->getCode();
        if (isset(self::$_paymentKeyGroups[$cacheKey][$feature])) {
            return self::$_paymentKeyGroups[$cacheKey][$feature];
        } elseif (!isset(self::$_paymentKeyGroups[$cacheKey])) {
            self::$_paymentKeyGroups[$cacheKey] = array();
        }

        //Get all default payment fields
        $paymentFields  = $this->_getPaymentMethod()->getAllPaymentInformationFields();
        $featureArray   = array();
        foreach ($paymentFields as $key => $features) {
            if (($feature & $features) === $feature) {
                $featureArray[] = $key;
            }
        }

        //Cache and return the result
        self::$_paymentKeyGroups[$cacheKey][$feature] = $featureArray;
        return $featureArray;
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