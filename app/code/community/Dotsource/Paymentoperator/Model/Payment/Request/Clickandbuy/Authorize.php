<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 13.01.2011 10:30:43
 *
 * Contributors:
 * mdaehnert - initial contents
 */

class Dotsource_Paymentoperator_Model_Payment_Request_Clickandbuy_Authorize
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{
    public function getRequestFile()
    {
        return 'ClickandBuy.aspx';
    }

    /**
     *
     * @see parent::_preProcessRequestData()
     */
    protected function _preProcessRequestData()
    {
        parent::_preProcessRequestData();

        $userData = $this->_getUserDataObject();

        $this
            ->getPayment()
            ->setIsTransactionPending(true)
            ->setTransactionPendingStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE);

        $userData
            ->setCapture(Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING)
            ->setPaymentCode($this->getPaymentMethod()->getCode());
    }

    /**
     *
     * @see parent::_getRequestData()
     */
    protected function _getRequestData()
    {
        $encryptData            = $this->_getEncryptionDataObject();
        $order                  = $this->getPayment()->getOrder();
        $billingAddress         = $order->getBillingAddress();
        $shippingAddress        = $order->getShippingAddress();
        $converter              = $conv = $this->_getConverter();

        // TODO: Is there a need to convert all these parameter on this method?;
        $tempOrderDesc          = $this->getPaymentMethod()->getConfigData('orderdesc');
        $convertedOrderDesc     = $converter->convertToUtf8($tempOrderDesc);
        $tempNationLanguage     = $billingAddress->getCountryModel()->getIso2Code();
        $trimmedNationLanguage  = strtolower(trim($tempNationLanguage));
        $billingStreet1          = $converter->splitStreet($billingAddress);
        $shippingStreet1         = $converter->splitStreet($shippingAddress);

        $encryptData['TransID']         = $this->_getIncrementId();
        $encryptData['RefNr']           = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['OrderDesc']       = $encryptData['TransID'] . ' ' . $convertedOrderDesc;
        $encryptData['Amount']          = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']        = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        // Allowed languages and nations are equal.
        $encryptData['Language']        = $trimmedNationLanguage;
        $encryptData['Nation']          = $trimmedNationLanguage;
        $encryptData['Response']        = 'encrypt';

        // URL settings.
        $encryptData['URLSuccess']      = Mage::getUrl('paymentoperator/callback_clickandbuy/success', array('_forced_secure' => true));
        $encryptData['URLFailure']      = Mage::getUrl('paymentoperator/callback_clickandbuy/failure', array('_forced_secure' => true));
        $encryptData['URLNotify']       = Mage::getUrl('paymentoperator/callback_clickandbuy/notify', array('_forced_secure' => true));

        // Billing Address.
        $encryptData['bdFirstName']     = $billingAddress->getFirstname();
        $encryptData['bdMiddleName']    = $billingAddress->getMiddlename();
        $encryptData['bdLastName']      = $billingAddress->getLastname();
        $encryptData['bdStreet']        = $billingStreet1->getStreetName();
        $encryptData['bdStreet2']       = $billingAddress->getStreet2();
        $encryptData['bdHouseNumber']   = $billingStreet1->getStreetNumber();
        $encryptData['bdZipCode']       = $billingAddress->getPostcode();
        $encryptData['bdCity']          = $billingAddress->getCity();
        $encryptData['bdCountryCode']   = $billingAddress->getCountryModel()->getIso3Code();
        $encryptData['bdState']         = $billingAddress->getRegion();

        // Shipping Address
        $encryptData['sdFirstName']     = $shippingAddress->getFirstname();
        $encryptData['sdMiddleName']    = $shippingAddress->getMiddlename();
        $encryptData['sdLastName']      = $shippingAddress->getLastname();
        $encryptData['sdStreet']        = $shippingStreet1->getStreetName();
        $encryptData['sdStreet2']       = $shippingAddress->getStreet2();
        $encryptData['sdHouseNumber']   = $shippingStreet1->getStreetNumber();
        $encryptData['sdZipCode']       = $shippingAddress->getPostcode();
        $encryptData['sdCity']          = $shippingAddress->getCity();
        $encryptData['sdCountryCode']   = $shippingAddress->getCountryModel()->getIso3Code();
        $encryptData['sdState']         = $shippingAddress->getRegion();
    }
}
