<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Helper_Depends
    extends Mage_Core_Helper_Abstract
{

    /**
     * Return an array of messages if the depends is not comply.
     *
     * @return  array
     */
    public function isPrefixRequired()
    {
        static $result = null;
        if ($result === null) {
            $result     = array();
            $attribute  = $this->_getCustomerAddressAttribute('prefix');

            //Check if the prefix is visible and required
            if (!$attribute->getIsVisible() || !$attribute->getIsRequired()) {
                $result = array(
                    $this->__("To use this payment method in the checkout the customer prefix field must be required.")
                );
            }
        }

        return $result;
    }

    /**
     * Return a attribute from the customer_address entity.
     *
     * @param   string  $attribute
     * @return  Mage_Eav_Model_Attribute
     */
    protected function _getCustomerAddressAttribute($attribute)
    {
        return $this->_getAttribute('customer_address', $attribute);
    }

    /**
     * Return a attribute from the given data.
     *
     * @param   string  $entity
     * @param   string  $attribute
     * @return  Mage_Eav_Model_Attribute
     */
    protected function _getAttribute($entity, $attribute)
    {
        return Mage::getSingleton('eav/config')->getAttribute($entity, $attribute);
    }
}