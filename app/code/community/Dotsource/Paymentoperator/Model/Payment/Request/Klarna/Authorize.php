<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 01.02.2011 07:52:17
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Klarna_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Klarna_Abstract
{
    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'Klarna.aspx';
    }

    /**
     *
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::_getRequestData()
     */
    protected function _getRequestData()
    {
        $this->_prepareGeneralData();
        $this->_prepareBillingData();
        $this->_prepareShippingData();
        $this->_prepareAdditionalData();
    }

    /**
     * Prepare general data fields.
     */
    protected function _prepareGeneralData()
    {
        $encryptData = $this->_getEncryptionDataObject();

        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['Amount']          = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']        = $this->_getCurrencyCode();
        $encryptData['OrderDesc']       = $this->_getOrderDesc();
    }

    /**
     * Prepare billing data fields.
     */
    protected function _prepareBillingData()
    {
        $encryptData    = $this->_getEncryptionDataObject();
        $order          = $this->getPayment()->getOrder();
        $billingAddress = $order->getBillingAddress();
        $converter      = $this->_getConverter();
        $billingStreet1 = $converter->splitStreet($billingAddress);

        // Billing data.
        if ($this->getPaymentMethod()->isBusinessCustomer()) {
            $encryptData['bdLastName']  = $billingAddress->getCompany();
        } else {
            $encryptData['bdFirstName'] = $billingAddress->getFirstname();
            $encryptData['bdLastName']  = $billingAddress->getLastname();
        }
        $encryptData['bdStreet']        = $billingStreet1->getStreetName();
        $encryptData['bdStreetNr']      = $billingStreet1->getStreetNumber();
        $encryptData['bdZip']           = $billingAddress->getPostcode();
        $encryptData['bdCity']          = $billingAddress->getCity();
        $encryptData['bdCountryCode']   = strtoupper($billingAddress->getCountryModel()->getIso3Code());
    }

    /**
     * Prepare shipping data fields.
     */
    protected function _prepareShippingData()
    {
        $encryptData        = $this->_getEncryptionDataObject();
        $order              = $this->getPayment()->getOrder();
        $shippingAddress    = $order->getShippingAddress();
        $converter          = $conv = $this->_getConverter();
        $shippingStreet1    = $converter->splitStreet($shippingAddress);
        $infoInstance       = $this->getPaymentMethod()->getInfoInstance();

        if ($this->getPaymentMethod()->isSsnNeeded()
            || $this->getPaymentMethod()->isCommercialRegisterNumberNeeded()
        ) {
            $encryptData['SocialSecurityNumber']    = $infoInstance->decrypt($infoInstance->getKlarnaSsn());
        }

        if ($this->getPaymentMethod()->isAnnualSalaryNeeded()) {
            $encryptData['AnnualSalary']            = $infoInstance->decrypt($infoInstance->getKlarnaAnnualSalary());
        }

        if ($this->getPaymentMethod()->isBusinessCustomer()) {
            $encryptData['sdLastName']              = $shippingAddress->getCompany();
            $encryptData['CompanyOrPerson']         = 'F';
        } else {
            $encryptData['sdFirstName']             = $shippingAddress->getFirstname();
            $encryptData['sdLastName']              = $shippingAddress->getLastname();
            $encryptData['DateOfBirth']             = $infoInstance->getKlarnaDob();
            $encryptData['Gender']                  = $infoInstance->getKlarnaGender();
            $encryptData['CompanyOrPerson']         = 'P';
        }

        $encryptData['sdStreet']                    = $shippingStreet1->getStreetName();
        $encryptData['sdStreetNr']                  = $shippingStreet1->getStreetNumber();
        $encryptData['sdZip']                       = $shippingAddress->getPostcode();
        $encryptData['sdCity']                      = $shippingAddress->getCity();
        $encryptData['sdCountryCode']               = strtoupper($shippingAddress->getCountryModel()->getIso3Code());
        $encryptData['MobileNr']                    = $shippingAddress->getTelephone();
        $encryptData['Email']                       = $this->getOracle()->getEmailAddress();
        $encryptData['IPAddr']                      = Mage::app()->getRequest()->getClientIp();
//        $encryptData['IPAddr']                      = '98.172.234.12'; //TODO: Remove
        $encryptData['KlarnaAction']                = $this->getPaymentMethod()->getKlarnaAction();
        $encryptData['InvoiceFlag']                 = $this->getPaymentMethod()->getConfigData('invoice_flag');
    }

    /**
     * Prepare additional data fields.
     *
     */
    protected function _prepareAdditionalData()
    {
        $encryptData    = $this->_getEncryptionDataObject();
        $order          = $this->getPayment()->getOrder();
        $billingAddress = $this->_getBillingAddress();

        if ($this->getPaymentMethod()->isReferenceNeeded()) {
            $encryptData['Reference']   = $billingAddress->getFirstname()
                . ' '
                . $billingAddress->getLastname();
        }
        $encryptData['OrderId1']        = $order->getIncrementId();
    }
}