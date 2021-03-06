<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Default_Reverse
    extends Dotsource_Paymentoperator_Model_Payment_Request_Request
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'reverse.aspx';
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request object
        $encryptData    = $this->_getEncryptionDataObject();

        //Set the encrypt data
        $encryptData['TransID']     = $this->_getIncrementId();
        $encryptData['PayID']       = $this->getReferencedTransactionModel()->getAdditionalInformation('payid');

        //Check if we should use amount
        if ($this->hasAmount()) {
            $encryptData['Amount']      = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
            $encryptData['Currency']    = $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        }
    }
}