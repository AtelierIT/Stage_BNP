<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.01.2011 16:46:33
 *
 * Contributors:
 * mdaehnert - initial contents
 *
 * TODO: If check for shipping method is added, then
 * TODO: extend every text: 'billing' with additional question for shipping (e.g. Add adressType as protected variable)
 */
class Dotsource_Paymentoperator_Model_Check_Address_Address
    extends Dotsource_Paymentoperator_Model_Check_Risk_Abstract
{
    const BLOCK_NAME_BILLING            = 'billing';
    const BLOCK_NAME_SHIPPING           = 'shipping';
    const BLOCK_TYPE_HANDLE_BILLING     = 'paymentoperator_checkout_onepage_billing_refresh';
    const BLOCK_TYPE_HANDLE_SHIPPING    = 'paymentoperator_checkout_onepage_shipping_refresh';
    const BLOCK_STEP_ALLOW              = 'allow';
    const BLOCK_STEP_COMPLETE           = 'complete';

    /** Holds the global code prefix */
    protected $_codePrefix          = 'paymentoperator_risk';

    /** Holds the address check code */
    protected $_code                = 'address_check';

    /** Holds the request model path */
    protected $_requestModelPath    = 'paymentoperator/check_address_request_request';

    /**
     *
     * @var Mage_Customer_Model_Address_Address
     */
    protected $_address = null;


    /**
     * Process address check.
     *
     */
    public function process()
    {
        $this->init($this->_getHelper()->getQuote()->getPayment());

        if (
            false === $this->isActive() ||
            true === $this->_doesAddressBodyIncludesError() ||
            false === $this->isAddressCheckNeeded()
        ) {
            return;
        }

        $this->_checkAddress();
    }


    /**
     * Set currently used address.
     *
     * @param Mage_Customer_Model_Address_Abstract $address
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Abstract
     */
    protected function _setAddress(Mage_Customer_Model_Address_Abstract $address)
    {
        $this->_address = $address;

        return $this;
    }


    /**
     * Return the current address.
     *
     * @return null|Mage_Customer_Model_Address_Abstract
     */
    protected function _getAddress()
    {
        return $this->_address;
    }


    /**
     *
     */
    protected function _checkAddress()
    {
        $request = $this->_getRequestModel();

        Mage::getModel('paymentoperator/connection')->sendRequest($request);

        // Unset address id here, because both following behaviours will reprint
        // the address template.
        $this->_removeCustomerAddressIdFromQuoteAddresses();

        //Check if the transaction has produce an error
        if ($request->getResponseModel()->hasError()) {
            $this->_prepareErrorByResponse($request);
        } else {
            $this->_saveCheckedAddressDataByRequest($request);
        }
    }


    /**
     * Prepare error for further usage.
     *
     * Steps:
     *      1.) Set exception via error managers (message + translation + session)
     *      2.) Set Update response for Magento
     * @param Dotsource_Paymentoperator_Model_Check_Address_Request_Request $request
     */
    protected function _prepareErrorByResponse($request)
    {
        //Clear the paymentoperator checked address hash to indicate an error for risk management
        $this->_getHelper()->getQuote()
            ->getBillingAddress()
            ->setPoCheckedAddressHash(null)
            ->save();

        //Create a request for the error manager
        $request = new Varien_Object(
            array(
                'message'               => $request->getResponseModel()->getResponse()->getCode(),
                'message_section'       => $this->getCode(),
                'section_area'          => null,
                'fallback'              => false,
                'use_callbacks'         => true,
                'arguments'             => array(),
                'is_error_code'         => true,
                'lock_checkout_step'    => true,
            )
        );

        //Process the request
        $this->_getHelper()->getPaymentHelper()->getPaymentErrorManager()
            ->removeHandler('exception')
            ->processChain($request);

        //Check if we block the checkout on error
        if ($request->getLockCheckoutStep()) {
            //Set the error message
            Mage::getSingleton('customer/session')->addNotice($request->getMessage());

            //Update the billing address
            $newResultBody = array();
            $newResultBody['update_section'] = array(
                'name' => self::BLOCK_NAME_BILLING,
                'html' => $this->_getAddressHtmlContent(self::BLOCK_TYPE_HANDLE_BILLING)
            );

            $this->_setResponseBody($newResultBody);
        }

        //In both cases save the quote
        $this->_getHelper()->getQuote()->save();
    }


    /**
     * Clear old response body and set new given one.
     *
     * @param array $responseBody
     */
    protected function _setResponseBody(array $responseBody)
    {
        Mage::app()->getResponse()->clearBody();
        Mage::app()->getResponse()->setBody(Zend_Json::encode($responseBody));
    }


    /**
     * Create the address html content.
     *
     * Also add dislpay block for session.
     *
     * @param self::BLOCK_TYPE_HANDLE_* $blockTypeHandle
     * @return string
     */
    protected function _getAddressHtmlContent($blockTypeHandle)
    {
        /* @var $layout Mage_Core_Model_Layout */
        $layout = Mage::getModel('core/layout');

        $messageStorage = Mage::getSingleton('customer/session');
        $layout->getMessagesBlock()->addMessages($messageStorage->getMessages(true));
        $layout->getUpdate()->resetHandles();
        $layout->getUpdate()->load($blockTypeHandle);
        $layout->generateXml();
        $layout->generateBlocks();

        return $layout->getOutput();
    }


    /**
     * Check if Magneto validation didn't return an error.
     *
     * Get the response from Magento and check for 'error' key.
     * @return boolean
     */
    protected function _doesAddressBodyIncludesError()
    {
        $result = $this->_getResponseBody();

        if (!empty($result['error'])) {
            return true;
        }

        return false;
    }


    /**
     * Get current response body.
     *
     * @return array
     */
    protected function _getResponseBody()
    {
        return Zend_Json::decode(Mage::app()->getResponse()->getBody());
    }


    /**
     * Return the customer address model to check.
     *
     * Steps:
     *      1.) Try to load the address from the address book
     *      2.) If we don't have a address from the address book
     *               use the current address from the quote.
     *
     * @return Mage_Customer_Model_Address_Abstract
     */
    public function getCustomerCheckAddress()
    {
        if (null === $this->_getAddress()) {
            $address = $this->_getCustomerAddressFromAddressBook();

            if (null === $address) {
                $address = $this->_getCustomerAddressFromQuote();
            }

            $this->_setAddress($address);
        }

        return $this->_getAddress();
    }


    /**
     * Check if we have an customer address id in the request.
     *
     * @return boolean
     */
    protected function _hasCustomerAddressIdInRequest()
    {
        $addressId = $this->_getCustomerAddressIdFromRequest();
        return !empty($addressId);
    }


    /**
     * Return a customer address id from quote.
     *
     * @return null|int
     */
    protected function _getCustomerAddressIdFromRequest()
    {
        return Mage::app()->getRequest()->getParam('billing_address_id', null);
    }


    /**
     * Remove the customer address id flag if the customer will create a new address.
     *
     * Workaround for Magento bug.
     */
    protected function _removeCustomerAddressIdFromQuoteAddresses()
    {
        if (!$this->_hasCustomerAddressIdInRequest()) {
            $this->getCustomerCheckAddress()
                ->setCustomerAddressId(null)
                ->save();
        }
    }


    /**
     * Return the current customer address from the request.
     *
     * @return null|Mage_Customer_Model_Address_Abstract
     */
    protected function _getCustomerAddressFromAddressBook()
    {
        //Try to load the address.
        if ($this->_hasCustomerAddressIdInRequest()) {
            $addressId  = $this->_getCustomerAddressIdFromRequest();
            $address    = Mage::getModel('customer/address')->load((int)$addressId);
            $addressId  = $address->getId();

            if (!empty($addressId)) {
                return $address;

            }
        }

        return null;
    }


    /**
     * Return the address from quote.
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _getCustomerAddressFromQuote()
    {
        return $this->_getHelper()->getQuote()->getBillingAddress();
    }


    /**
     * Check if the address has to be (re)checked.
     *
     * Need-for-check-situatiion:
     *      country is DE
     *      no old value or
     *      hash changed.
     *
     * @return boolean
     */
    public function isAddressCheckNeeded()
    {
        $billingCountry = $this->_getHelper()->getQuote()->getBillingAddress()
            ->getCountryModel()->getIso2Code();

        if ('DE' !== $billingCountry) {
            return false;
        }

        $oldHash = $this->getCustomerCheckAddress()->getPoCheckedAddressHash();
        $newHash = $this->_createHashValueFromAddress();

        return $oldHash !== $newHash;
    }


    /**
     * Return current hash value from address.
     *
     * Hash parameters are the attributes also sent via paymentoperator address check - request.
     * Return value will be made of md5.
     *
     * @return string
     */
    protected function _createHashValueFromAddress()
    {
        $address            = $this->getCustomerCheckAddress();
        $attribHashKeys     = array('lastname', 'firstname', 'postcode', 'city');
        $hashData           = array();

        foreach ($attribHashKeys as $key) {
            $hashData[] = $address->getData($key);
        }

        // Manually set street 1.
        $hashData[] = $address->getStreet1();

        $hashString = '|' . implode(';', $hashData) . '|';

        return md5($hashString);
    }


    /**
     * Save response data back to model.
     *
     * @param Dotsource_Paymentoperator_Model_Check_Address_Request_Request $request
     */
    protected function _saveCheckedAddressDataByRequest($request)
    {
        $response               = $request->getResponseModel()->getResponse();
        $street1                = $response->getData('strasse') . ' ' . $response->getData('hno');
        $attribKeys             = array(
            'lastname'  => 'name',
            'firstname' => 'vorname',
            'postcode'  => 'plz',
            'city'      => 'ort'
        );
        $newCheckedAddressHash  = '';
        $loopAddresses          = $this->_getUpdateAddresses();

        foreach ($loopAddresses as $address) {
            // Set values from attributes.
            foreach ($attribKeys as $attribName => $responseName) {
                $address->setData($attribName, $response->getData($responseName));
            }

            // Retrieve array of streets, fill street 1 with response data and save it.
            $fullStreet             = $address->getStreet(null);
            $fullStreet[0]          = $street1;

            $address->setStreetFull($fullStreet);

            // Set the new hash.
            $newCheckedAddressHash  = $this->_createHashValueFromAddress();

            $address->setPoCheckedAddressHash($newCheckedAddressHash);

            // Save the changes.
            $address->save();
        }

        $this->_setPasswordToQuote();
        $this->_getHelper()->getQuote()->save();
        $this->_setSuccessResponseBody();
    }


    /**
     * Get the needed addresses for update.
     *
     * @return array
     */
    protected function _getUpdateAddresses()
    {
        $loopAddresses          = array();
        $billingQuoteAddress    = $this->_getHelper()->getQuote()->getBillingAddress();
        $shippingQuoteAddress   = $this->_getHelper()->getQuote()->getShippingAddress();

        // Add used check address.
        $loopAddresses[]        = $this->getCustomerCheckAddress();

        // Add billing_quote_addresss if current address is a customer_address.
        if ($this->getCustomerCheckAddress() instanceof Mage_Customer_Model_Address) {
            $loopAddresses[] = $billingQuoteAddress;
        }

        // Add shipping_quote_address if billing = same as shipping.
        if ($shippingQuoteAddress->getSameAsBilling()) {
            $loopAddresses[] = $shippingQuoteAddress;
        }

        return $loopAddresses;
    }


    /**
     * Write customers password back to billing address if method is register.
     *
     */
    protected function _setPasswordToQuote()
    {
        $quote = $this->_getHelper()->getQuote();

        if ($quote->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            $decryptedPasswordHash =
                Mage::helper('core')->decrypt($this->_getHelper()->getQuote()->getPasswordHash());

            $this->_getHelper()->getQuote()->getBillingAddress()
                ->setCustomerPassword($decryptedPasswordHash)
                ->setConfirmPassword($decryptedPasswordHash);
        }
    }


    /**
     *
     * Add Billing update to response.
     */
    protected function _setSuccessResponseBody()
    {
        // Update the billing_html section.
        $newResultBody = $this->_getResponseBody();

        $newResultBody['billing']['update_section'] = array(
            'name' => self::BLOCK_NAME_BILLING,
            'html' => $this->_getAddressHtmlContent(self::BLOCK_TYPE_HANDLE_BILLING)
        );

        // Update the shipping template.
        $shippingUpdateSection = array(
            'name' => self::BLOCK_NAME_SHIPPING,
            'html' => $this->_getAddressHtmlContent(self::BLOCK_TYPE_HANDLE_SHIPPING)
        );

        // Either add new update section or merge the key => values into existing array.
        if ($this->_getHelper()->getQuote()->getShippingAddress()->getSameAsBilling()) {
            $this->_setSectionData(self::BLOCK_NAME_SHIPPING, self::BLOCK_STEP_COMPLETE);

            $newResultBody['shipping']['update_section'] = $shippingUpdateSection;
        } else {
            $newResultBody = array_merge($newResultBody, $shippingUpdateSection);
        }

        // Workaround, because calling the billing block earlier will always unset this >complete value<.
        $this->_setSectionData(self::BLOCK_NAME_BILLING, self::BLOCK_STEP_COMPLETE);

        $this->_setResponseBody($newResultBody);
    }


    /**
     *
     *  @param self::BLOCK_NAME_* $stepName
     *  @param self::BLOCK_STEP_* $stepValue
     */
    protected function _setSectionData($stepName, $stepValue)
    {
        Mage::helper('paymentoperator')->getFrontendCheckoutSession()
            ->setStepData($stepName, $stepValue, true);
    }
}