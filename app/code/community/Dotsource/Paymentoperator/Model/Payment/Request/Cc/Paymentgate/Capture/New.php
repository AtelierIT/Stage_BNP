<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Cc_Paymentgate_Capture_New
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'direct.aspx';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request object
        $encryptData    = $this->_getEncryptionDataObject();
        $orderDesc      = $this->_getConverter()->convertToUtf8(
            $this->getPaymentMethod()->getConfigData('orderdesc')
        );

        //Set the encrypt data
        $encryptData['TransID']     = $this->_getIncrementId();
        $encryptData['RefNr']       = $this->getPaymentoperatorTransactionModel()->getTransactionCode();
        $encryptData['OrderDesc']   = $encryptData['TransID'] . " " . $orderDesc;

        $encryptData['Amount']      = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']    = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        $encryptData['Capture']     = 'AUTO';

        //Set the cc number
        $encryptData['CCNr']        = Mage::helper('core')->getEncryptor()->decrypt(
            $this->getPayment()->getCcNumberEnc()
        );
        $this->_addDangerousTag('CCNr');

        //Cc expire date
        $encryptData['CCExpiry']    = $this->_getHelper()->getConverter()->getPaymentoperatorExpireDate(
            $this->getPayment()->getCcExpMonth(),
            $this->getPayment()->getCcExpYear()
        );

        //Set the brand
        $encryptData['CCBrand']     = $this->_getHelper()->getConverter()->getCcType(
            $this->getPayment()->getCcType(),
            Dotsource_Paymentoperator_Helper_Converter::PAYMENTOPERATOR_TYPE
        );
    }
}