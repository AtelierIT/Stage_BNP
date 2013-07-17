<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Abstract
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'billpay.aspx';
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::_getRequestData()
     */
    protected function _getRequestData()
    {
        $this->_setGeneralData();
        $this->_setBillingData();
        $this->_setShippingData();
        $this->_setOrderData();
    }

    /**
     * Prepare general data fields.
     */
    protected function _setGeneralData()
    {
        /* @var $paymentMethod Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract */
        $encryptData                    = $this->_getEncryptionDataObject();
        $paymentMethod                  = $this->getPaymentMethod();

        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['Amount']          = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']        = $this->_getCurrencyCode();

        //Set specific company data
        if ($paymentMethod->isCompany()) {
            $encryptData['CompanyOrPerson'] = 'F';
            $encryptData['CompanyName']     = $paymentMethod->getCompanyName();
            $encryptData['LegalForm']       = $paymentMethod->getCompanyLegalForm();
        } else {
            $encryptData['CompanyOrPerson'] = 'P';
        }

        //Set the date of birth if needed
        if ($paymentMethod->isDobNeeded()) {
            $encryptData['DateOfBirth'] = $paymentMethod->getDob('yyyy-MM-dd');
        }

        $encryptData['BillPayAction']   = $paymentMethod->getBillpayAction();
        $encryptData['Language']		= Mage::app()->getLocale()->getLocale()->getLanguage();

        //Is new customer?
        if ($this->getOracle()->isGuestCheckout()) {
            $encryptData['NewCustomer'] = 'GUEST';
        } elseif ($this->getOracle()->isRegisterCheckout()) {
            $encryptData['NewCustomer'] = 'YES';
        } else {
            $encryptData['NewCustomer'] = 'NO';
        }

        $encryptData['GtcValue']		= 'YES';
        $encryptData['IPAddr']          = Mage::app()->getRequest()->getClientIp();
        $encryptData['BpMethod']        = 'Base';
    }

    /**
     * Prepare billing data fields.
     */
    protected function _setBillingData()
    {
        $encryptData    = $this->_getEncryptionDataObject();
        $converter      = $this->_getConverter();

        //Get the billing address
        $billingAddress = $this->getOracle()->getBillingAddress();
        $billingStreet1 = $converter->splitStreet($billingAddress);

        $encryptData['bdSalutation']    = $billingAddress->getPrefix();
        $encryptData['bdFirstName'] 	= $billingAddress->getFirstname();
        $encryptData['bdLastName']  	= $billingAddress->getLastname();
        $encryptData['bdStreet']        = $billingStreet1->getStreetName();
        $encryptData['bdStreetNr']      = $billingStreet1->getStreetNumber();

        //Check if we have an extra address information
        if ($billingAddress->getStreet(2)) {
            $encryptData['bdStreet']    = $billingAddress->getStreet(2);
        }

        $encryptData['bdZip']           = $billingAddress->getPostcode();
        $encryptData['bdCity']          = $billingAddress->getCity();
        $encryptData['bdCountryCode']   = strtoupper($billingAddress->getCountryModel()->getIso3Code());
        $encryptData['Email']           = $this->getOracle()->getEmailAddress();
        $encryptData['Phone']        	= $billingAddress->getTelephone();
    }

    /**
     * Prepare shipping data fields.
     */
    protected function _setShippingData()
    {
        $encryptData = $this->_getEncryptionDataObject();

        if ($this->getOracle()->getModel()->getIsVirtual()
            || $this->getOracle()->isShippingAddressEqualToBillingAddress()
        ) {
            $encryptData['UseBillingData']  = 'Yes';
        } else {
            //Split the shipping address
            $converter                      = $this->_getConverter();
            $shippingAddress                = $this->getOracle()->getShippingAddress();
            $shippingStreet1                = $converter->splitStreet($shippingAddress);

            $encryptData['UseBillingData']  = 'No';
            $encryptData['sdSalutation']    = $shippingAddress->getPrefix();
            $encryptData['sdFirstName']     = $shippingAddress->getFirstname();
            $encryptData['sdLastName']      = $shippingAddress->getLastname();
            $encryptData['sdStreet']        = $shippingStreet1->getStreetName();
            $encryptData['sdStreetNr']      = $shippingStreet1->getStreetNumber();

            //Check if we have an extra address information
            if ($shippingAddress->getStreet(2)) {
                $encryptData['bdStreet']    = $shippingAddress->getStreet(2);
            }

            $encryptData['sdZip']           = $shippingAddress->getPostcode();
            $encryptData['sdCity']          = $shippingAddress->getCity();
            $encryptData['sdCountryCode']   = strtoupper($shippingAddress->getCountryModel()->getIso3Code());
        }
    }

    /**
     * Prepare Order Data
     */
    protected function _setOrderData()
    {
        $encryptData = $this->_getEncryptionDataObject();

        $encryptData['ArticleList'] = $this->_getArticleList();
        $encryptData['OrderDesc']   = $this->_getOrderDesc();
    }
}