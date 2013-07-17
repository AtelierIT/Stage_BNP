<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 24.01.2011 16:49:33
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Block_Rewrite_Checkout_Onepage_Billing
    extends Mage_Checkout_Block_Onepage_Billing
{
    /**
     * Workaround.
     *
     * Always try to get the already instanciated billing address
     * instead of getting a new one when not logged in.
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

    /**
     * Overwritten.
     *
     * Bugfix: Show last set billing address in select field. Not the first entry.
     *
     * @param mixed $type
     * @return mixed
     */
    public function getAddressesHtmlSelect($type)
    {
        if ($this->isCustomerLoggedIn()) {
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value'=>$address->getId(),
                    'label'=>$address->format('oneline')
                );
            }

            //Get the customer address id
            $addressId = $this->getAddress()->getCustomerAddressId();

            if (empty($addressId)) {
                $addressId = '';
            }

            $select = $this->getLayout()->createBlock('core/html_select')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setClass('address-select')
                ->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
                ->setValue($addressId)
                ->setOptions($options);

            $select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }
        return '';
    }

    /**
     * Return true if the current address is from the address book.
     *
     * @return boolean
     */
    public function isCustomerAddressFromAddressBook()
    {
        $addressId = $this->getAddress()->getCustomerAddressId();
        return !empty($addressId);
    }

    /**
     * Check if the customer use the guest checkout.
     *
     * @return boolean
     */
    public function isGuestCheckout()
    {
        return Mage_Checkout_Model_Type_Onepage::METHOD_GUEST == $this->getQuote()->getCheckoutMethod();
    }
}
