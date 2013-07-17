<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Oracle_Type_Order
{

    /** Order const */
    const ORDER                     = 1;

    /** Quote const */
    const QUOTE                     = 2;


    /** Holds the model */
    protected $_model               = null;

    /** Holds the type of the model */
    protected $_type                = null;

    /** Holds the gender option array */
    protected $_genderOptionArray   = null;


    /**
     * Set a the use model.
     *
     * @param $model
     * @return Dotsource_Paymentoperator_Model_Oracle_Type_Order
     */
    public function setModel($model)
    {
        //Check the model object
        if ($model instanceof Mage_Sales_Model_Order) {
            $this->_type = self::ORDER;
        } elseif ($model instanceof Mage_Sales_Model_Quote) {
            $this->_type = self::QUOTE;
        } elseif ($model instanceof Mage_Sales_Model_Order_Payment) {
            $this->_type = self::ORDER;
            $model = $model->getOrder();
        } elseif ($model instanceof Mage_Sales_Model_Quote_Payment) {
            $this->_type = self::QUOTE;
            $model = $model->getQuote();
        } elseif ($model instanceof Mage_Payment_Model_Method_Abstract) {
            $this->setModel($model->getInfoInstance());
            return;
        } else {
            Mage::throwException('Can\'t process "' . get_class($model) . '" object.');
        }

        //Set the model
        $this->_model = $model;
        return $this;
    }

    /**
     * Return the increment id from the order or the reserved order id
     * from the quote.
     *
     * @return string
     */
    public function getIncrementId()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getIncrementId();
            case self::QUOTE:
                return $this->getModel()->reserveOrderId()->getReservedOrderId();
        }
    }

    /**
     * Return the order / quote base currency code.
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        switch ($this->getType()) {
            case self::ORDER:
            case self::QUOTE:
                return $this->getModel()->getBaseCurrencyCode();
        }
    }

    /**
     * Return the order / quote currency code.
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getOrderCurrencyCode();
            case self::QUOTE:
                return $this->getModel()->getQuoteCurrencyCode();
        }
    }

    /**
     * Return the base grand total from the model.
     *
     * @return string
     */
    public function getBaseGrandTotal()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getBaseGrandTotal();
            case self::QUOTE:
                return $this->getModel()->collectTotals()->getBaseGrandTotal();
        }
    }

    /**
     * Return the grand total from the model.
     *
     * @return string
     */
    public function getGrandTotal()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getGrandTotal();
            case self::QUOTE:
                return $this->getModel()->collectTotals()->getGrandTotal();
        }
    }

    /**
     * Return the base grand total without tax from the model.
     *
     * @return  string
     */
    public function getBaseGrandTotalExclTax()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getBaseGrandTotal() - $this->getModel()->getBaseTaxAmount();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                if ($this->getModel()->getIsVirtual()) {
                    $address = $this->getBillingAddress();
                } else {
                    $address = $this->getShippingAddress();
                }

                return $this->getBaseGrandTotal() - $address->getBaseTaxAmount();
        }
    }

    /**
     * Return the grand total without tax from the model.
     *
     * @return  string
     */
    public function getGrandTotalExclTax()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getGrandTotal() - $this->getMoldel()->getTaxAmount();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                if ($this->getModel()->getIsVirtual()) {
                    $address = $this->getBillingAddress();
                } else {
                    $address = $this->getShippingAddress();
                }

                return $this->getGrandTotal() - $address->getTaxAmount();
        }
    }

    /**
     * Return the sub total without tax from the model.
     *
     * @return  string
     */
    public function getSubTotal()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getSubtotal();
            case self::QUOTE:
                return $this->getModel()->collectTotals()->getSubtotal();
        }
    }

    /**
     * Return the base sub total without tax from the model.
     *
     * @return  string
     */
    public function getBaseSubTotal()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getBaseSubtotal();
            case self::QUOTE:
                return $this->getModel()->collectTotals()->getBaseSubtotal();
        }
    }

    /**
     * Return the sub total with tax from the model.
     *
     * @return  string
     */
    public function getSubTotalInclTax()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getSubtotalInclTax();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                return $this->_getSourceAddress()->getSubtotalInclTax();
        }
    }

    /**
     * Return the base sub total with tax from the model.
     *
     * @return  string
     */
    public function getBaseSubTotalInclTax()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getBaseSubtotalInclTax();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                return $this->_getSourceAddress()->getBaseSubtotalInclTax();
        }
    }

    /**
     * Return the shipping amount without tax from the model.
     *
     * @return  string
     */
    public function getShippingAmount()
    {
        //No shipping costs?
        if ($this->getModel()->getIsVirtual()) {
            return "0";
        }

        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getShippingAmount();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                return $this->getShippingAddress()->getShippingAmount();
        }
    }

    /**
     * Return the base shipping amount without tax from the model.
     *
     * @return  string
     */
    public function getBaseShippingAmount()
    {
        //No shipping costs?
        if ($this->getModel()->getIsVirtual()) {
            return "0";
        }

        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getBaseShippingAmount();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                return $this->getShippingAddress()->getBaseShippingAmount();
        }
    }

    /**
     * Return the shipping amount with tax from the model.
     *
     * @return  string
     */
    public function getShippingAmountInclTax()
    {
        //No shipping costs?
        if ($this->getModel()->getIsVirtual()) {
            return "0";
        }

        switch ($this->getType()) {
            case self::ORDER:
                return $this->getShippingAmount() + $this->getModel()->getShippingTaxAmount();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                return $this->getShippingAmount() + $this->_getSourceAddress()->getShippingTaxAmount();
        }
    }

    /**
     * Return the base shipping amount with tax from the model.
     *
     * @return  string
     */
    public function getBaseShippingAmountInclTax()
    {
        //No shipping costs?
        if ($this->getModel()->getIsVirtual()) {
            return "0";
        }

        switch ($this->getType()) {
            case self::ORDER:
                return $this->getBaseShippingAmount() + $this->getModel()->getBaseShippingTaxAmount();
            case self::QUOTE:
                $this->getModel()->collectTotals();
                return $this->getBaseShippingAmount() + $this->_getSourceAddress()->getBaseShippingTaxAmount();
        }
    }

    /**
     * Return the shipping description from the model.
     *
     * @return  string
     */
    public function getShippingDescription()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getShippingDescription();
            case self::QUOTE:
                if ($this->getModel()->getIsVirtual()) {
                    return '';
                }

                return $this->getShippingAddress()->getShippingDescription();
        }
    }

    /**
     * Return the discount amount with tax from the current model.
     *
     * @return  string
     */
    public function getDiscountAmount()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getDiscountAmount();
            case self::QUOTE:
                return $this->_getSourceAddress()->getDiscountAmount();
        }
    }

    /**
     * Return the base discount amount with tax from the current model.
     *
     * @return  string
     */
    public function getBaseDiscountAmount()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getBaseDiscountAmount();
            case self::QUOTE:
                return $this->_getSourceAddress()->getBaseDiscountAmount();
        }
    }

    /**
     * Return the discount amount without the tax from the current model.
     *
     * @return  string
     */
    public function getBaseDiscountAmountExclTax()
    {
        //check if we have something to calculate
        if (!$this->getBaseDiscountAmount()) {
            return "0";
        }

        //Get the right shipping address
        $shippingAddress    = false;
        if (!$this->getModel()->getIsVirtual()) {
            $shippingAddress    = $this->getShippingAddress();
        }
        $billingAddress     = $this->getBillingAddress();
        $store              = $this->getModel()->getStore();

        //Bridge
        if ($this->isOrder()) {
            /* @var $order Mage_Sales_Model_Order */
            $order              = $this->getModel();
            $customer           = $this->getCustomer();

            //Get the customer group to get the customer tax class
            $customerGroup      = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
            if ($customer) {
                $customerGroup = $customer->getGroupId();
            }
            $customerTaxClassId = Mage::getModel('customer/group')->getTaxClassId($customerGroup);
        } else {
            /* @var $quote Mage_Sales_Model_Quote */
            $quote              = $this->getModel();
            $customerTaxClassId = $quote->getCustomerTaxClassId();
        }

        /* @var $taxCalculationModel Mage_Tax_Model_Calculation */
        $taxCalculationModel = Mage::getSingleton('tax/calculation');
        $request = $taxCalculationModel->getRateRequest(
            $shippingAddress,
            $billingAddress,
            $customerTaxClassId,
            $store
        );

        //Get the first rate we can get for the calculations
        foreach ($this->getModel()->getAllItems() as $item) {
            $rate = $taxCalculationModel->getRate(
                $request->setProductClassId($item->getProduct()->getTaxClassId())
            );

            //We use the first valid rate
            if ($rate) {
                break;
            }
        }

        //Do the calculation
        $amount = $this->getBaseDiscountAmount();
        if ($rate) {
            $amount = $amount / (1 + ($rate / 100.0));
        }

        return round($amount, 2);
    }

    /**
     * Return a unique hash (md5) to detect payment changes.
     *
     * @return  string
     */
    public function getUniquePaymentHash()
    {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        /* @var $methodInstance Dotsource_Paymentoperator_Model_Payment_Abstract */
        /* @var $item Mage_Sales_Model_Quote_Item */
        $payment            = $this->getModel()->getPayment();
        $methodInstance     = $payment->getMethodInstance();
        $converter          = $this->_getHelper()->getConverter();
        $grandTotal         = $converter->formatPrice($this->getGrandTotal(), $this->getCurrencyCode());
        $baseGrandTotal     = $converter->formatPrice($this->getBaseGrandTotal(), $this->getBaseCurrencyCode());
        $shippingAmount     = $converter->formatPrice($this->getShippingAmount(), $this->getCurrencyCode());
        $baseShippingAmount = $converter->formatPrice($this->getBaseShippingAmount(), $this->getBaseCurrencyCode());

        //Fixed data
        $data = array(
            'increment_id'              => $this->getIncrementId(),
            'order_grand_total'         => $grandTotal,
            'order_base_grand_total'    => $baseGrandTotal,
            'order_currency'            => $this->getCurrencyCode(),
            'order_base_currency'       => $this->getBaseCurrencyCode(),
            'shipping_amount'           => $shippingAmount,
            'shipping_base_amount'      => $baseShippingAmount,
            'payment_method'            => $payment->getMethod(),
        );

        //Get all payment fields
        if ($methodInstance instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            //Get all payment keys
            $paymentUniqueFields = $methodInstance->getPaymentInformationModel()->getFeaturedPaymentFields(
                Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY
            );

            //Get all payment fields that are encrypted
            $encryptedFields = $methodInstance->getPaymentInformationModel()->getFeaturedPaymentFields(
                Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY | Dotsource_Paymentoperator_Model_Paymentinformation::ENCRYPTED
            );

            //Add the payment data to the hash process
            foreach ($paymentUniqueFields as $field) {
                //Field value
                $fieldData = $payment->getData($field);

                //Decrypt the data if needed
                if (in_array($field, $encryptedFields)) {
                    $fieldData = $payment->decrypt($fieldData);
                }

                if ($fieldData) {
                    $data["payment_{$field}"] = (string) $fieldData;
                }
            }
        }

        //Add all item information to the hash
        foreach ($this->getModel()->getAllItems() as $item) {
            $key = "item_{$item->getProductId()}_";

            //Add the sku
            $data["{$key}_sku"] = $item->getSku();

            //Add the qty
            if ($this->isOrder()) {
                $data["{$key}_qty"] = $item->getQtyOrdered();
            } else {
                $data["{$key}_qty"] = $item->getQty();
            }

            //Add the price
            $rowTotal       = $converter->formatPrice($item->getRowTotalInclTax(), $this->getCurrencyCode());
            $baseRowTotal   = $converter->formatPrice($item->getBaseRowTotalInclTax(), $this->getBaseCurrencyCode());
            $data["{$key}_row_total"]       = $rowTotal;
            $data["{$key}_base_row_total"]  = $baseRowTotal;
        }

        //Call a event to allow to extend the hash parts
        $transferObject = new stdClass();
        $transferObject->data = $data;
        Mage::dispatchEvent(
            'paymentoperator_build_unique_payment_hash',
            array(
                'transfer_object'   => $transferObject,
                'oracle'            => $this,
            )
        );
        $data = $transferObject->data;

        //Build the key as string
        $dataString = '';
        $seperator  = '#';
        ksort($data, SORT_STRING);
        foreach ($data as $key => $value) {
            $dataString .= "{$seperator}{$key}:{$value}{$seperator}";
        }

//        Mage::log($data);
//        Mage::log(md5("{$seperator}{$dataString}{$seperator}")."\n\n\n");

        //Create a hash from the data key
        return md5("{$seperator}{$dataString}{$seperator}");
    }

    /**
     * Return the billing address.
     *
     * @return  Mage_Customer_Model_Address_Abstract
     */
    public function getBillingAddress()
    {
        return $this->getModel()->getBillingAddress();
    }

    /**
     * Return the shipping address.
     *
     * @return  Mage_Customer_Model_Address_Abstract
     */
    public function getShippingAddress()
    {
        return $this->getModel()->getShippingAddress();
    }

    /**
     * Returns the source address for the prices. This method is only valid if
     * the model is an quote in all other cases the method throws an exception.
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _getSourceAddress()
    {
        if ($this->isQuote()) {
            if ($this->getModel()->getIsVirtual()) {
                return $this->getBillingAddress();
            } else {
                return $this->getShippingAddress();
            }
        }

        throw new Exception("This method can only call if the model is a quote.");
    }

    /**
     * Check if the shipping address is equal to the billing address.
     *
     * @return  boolean
     */
    public function isShippingAddressEqualToBillingAddress()
    {
        //Check if we have only virtual products so we have only one address
        if ($this->getModel()->getIsVirtual()) {
            return true;
        }

        $billingAddress         = $this->getBillingAddress();
        $shippingAddress        = $this->getShippingAddress();

        //Default assumption is unknown
        $resultObject           = new stdClass();
        $resultObject->equal    = null;
        Mage::dispatchEvent(
            'paymentoperator_addresses_equal',
            array(
                'billing_address'   => $billingAddress,
                'shipping_address'  => $shippingAddress,
                'result_object'     => $resultObject,
            )
        );

        //Return the result from the event
        if (null !== $resultObject->equal) {
            return (boolean) $resultObject->equal;
        }

        //This fields should be equal
        $equalFields = array(
            'region_id',
            'region',
            'postcode',
            'lastname',
            'street',
            'city',
            'country_id',
            'firstname',
            'prefix',
            'middlename',
            'suffix',
            'company',
        );

        //Call a event to provide the possibility to change the fields
        $resultObject           = new stdClass();
        $resultObject->fields   = $equalFields;
        Mage::dispatchEvent(
            'paymentoperator_addresses_equal_extend_fields',
            array(
                'billing_address'   => $billingAddress,
                'shipping_address'  => $shippingAddress,
                'result_object'     => $resultObject,
            )
        );
        $equalFields = $resultObject->fields;

        //Check the fields
        foreach ($equalFields as $field) {
            //Get the values
            $billingAddressValue    = strtolower(trim($billingAddress->getData($field)));
            $shippingAddressValue   = strtolower(trim($shippingAddress->getData($field)));

            //Compare
            if ($billingAddressValue != $shippingAddressValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return true if the customer is a guest.
     *
     * @return  boolean
     */
    public function isGuestCheckout()
    {
        switch ($this->getType()) {
            case self::ORDER:
                return $this->getModel()->getCustomerIsGuest();
            case self::QUOTE:
                return Mage_Checkout_Model_Type_Onepage::METHOD_GUEST === $this->getModel()->getCheckoutMethod();
        }
    }

    /**
     * Return true if the customer is a guest.
     *
     * @return  boolean
     */
    public function isRegisterCheckout()
    {
        switch ($this->getType()) {
            case self::ORDER:
                //We can't resolve the checkout type from order
                return false;
            case self::QUOTE:
                return Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER === $this->getModel()->getCheckoutMethod();
        }
    }

    /**
     * Return the email address from the given model.
     *
     * @return  string|null
     */
    public function getEmailAddress()
    {
        return $this->_getCustomerWidget('email');
    }

    /**
     * Return the customers dob.
     *
     * @param string format     Format the dob in the given format.
     *                          If the format is empty the Zend_Date will return
     *                          For more information see http://framework.zend.com/manual/en/zend.date.constants.html
     *                          German format:  dd.MM.yyyy
     *                          ISO 8601:       yyyy-MM-dd
     * @return  Zend_Date|string|null
     */
    public function getDob($format = null)
    {
        $dob = $this->_getCustomerWidget('dob');

        //Check if we need to format the dob is not valid
        if (!$dob) {
            return null;
        }

        //Create the zend date
        $zendDateDob = Mage::app()->getLocale()->date($dob, null, null, false);

        //Return the dob in the given format
        if ($format) {
            return $zendDateDob->toString($format);
        }

        return $zendDateDob;
    }

    /**
     * Return the prefix from the customer.
     *
     * @return  string
     */
    public function getPrefix()
    {
        return $this->_getCustomerWidget('prefix');
    }

    /**
     * Return the select gender.
     *
     * @return  string|null
     */
    public function getGender()
    {
        return $this->_getCustomerWidget('gender');
    }

    /**
     * Return true if the customer gender is male.
     *
     * @return  boolean
     */
    public function isGenderMale()
    {
        return $this->_isGender('male');
    }

    /**
     * Return true if the customer gender is female.
     *
     * @return  boolean
     */
    public function isGenderFemale()
    {
        return $this->_isGender('female');
    }

    /**
     * Return if the request gender an the current gender are equal.
     *
     * @param   string  $requestGender male / female
     * @return  boolean
     */
    protected function _isGender($requestGender)
    {
        if (!$this->hasGender()) {
            return false;
        }

        //Check for gender type
        $gender     = $this->getGender();
        $genders    = $this->_getGenders();

        return strtolower(trim($genders[$gender])) === strtolower(trim($requestGender));
    }

    /**
     * Return true if have a valid gender.
     *
     * @return  boolean
     */
    public function hasGender()
    {
        $gender     = $this->getGender();
        $genders    = $this->_getGenders();

        return $gender && $genders && isset($genders[$gender]);
    }

    /**
     * Return the the customer model is available.
     *
     * @return  Mage_Customer_Model_Customer || null
     */
    public function getCustomer()
    {
        //Try to get the customer from the customer session
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        //Return the valid customer object
        if ($this->_isCustomerValid($customer)) {
            return $customer;
        }

        //Try to get the customer from the current model
        $customer = $this->getModel()->getCustomer();

        //Return the valid customer object
        if ($this->_isCustomerValid($customer)) {
            return $customer;
        }

        //Try to load the customer object from a id
        if ($this->getModel()->getCustomerId()) {
            $customer = Mage::getModel('customer/customer')->load(
                $this->getModel()->getCustomerId()
            );

            if ($this->_isCustomerValid($customer)) {
                return $customer;
            }
        }

        return null;
    }

    /**
     * Return true if the given customer object is valid.
     *
     * @param   mixed   $customerObject
     * @return  boolean
     */
    protected function _isCustomerValid($customerObject)
    {
        return $customerObject
            && $customerObject instanceof Mage_Customer_Model_Customer
            && $customerObject->getId();
    }

    /**
     * Return a customer widget attribute.
     *
     * @param   string      $field
     * @return  string|null
     */
    protected function _getCustomerWidget($field)
    {
        //Check for an customer object from the model
        $customer = $this->getCustomer();

        //Try to get the customer widget from the customer object
        if ($customer
            && $customer instanceof Mage_Customer_Model_Customer
            && $customer->getData($field)
        ) {
            return $customer->getData($field);
        }

        //For non address models add the customer_ prefix
        foreach (array($field, "customer_$field") as $field) {
            //Try to get the customer widget from the checkout environment
            if ($this->getBillingAddress()->getData($field)) {
                return $this->getBillingAddress()->getData($field);
            } elseif ($this->getModel()->getData($field)) {
                return $this->getModel()->getData($field);
            }
        }

        return null;
    }

    /**
     * Return all genders.
     *
     * @return  array
     */
    protected function _getGenders()
    {
        if (null === $this->_genderOptionArray) {
            $collectionData = Mage::getResourceModel('eav/entity_attribute_option_collection')
                ->setPositionOrder('asc')
                ->setAttributeFilter(Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getId())
                ->setStoreFilter(0)
                ->load()
                ->toOptionArray();

            //Holds the options
            $this->_genderOptionArray = array();

            //Convert the collection data
            foreach ($collectionData as $column) {
                $this->_genderOptionArray[$column['value']] = $column['label'];
            }
        }

        return $this->_genderOptionArray;
    }

    /**
     * Return the object model.
     *
     * @return  Mage_Sales_Model_Order
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * Check if we current model is an order.
     *
     * @return  boolean
     */
    public function isOrder()
    {
        return self::ORDER === $this->_type;
    }

    /**
     * Check if we current model is an quote.
     *
     * @return  boolean
     */
    public function isQuote()
    {
        return self::QUOTE === $this->_type;
    }

    /**
     * Return the type of the current model.
     *
     * @return  int
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Return the paymentoperator helper.
     *
     * @return  Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}