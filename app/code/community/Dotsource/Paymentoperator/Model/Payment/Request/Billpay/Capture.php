<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Capture
    extends Dotsource_Paymentoperator_Model_Payment_Request_Default_Capture
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return "capture.aspx";
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Authorize::_getRequestData()
     */
    protected function _getRequestData()
    {
        parent::_getRequestData();

        /* @var $paymentMethod Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract */
        $encryptData            = $this->_getEncryptionDataObject();
        $paymentMethod          = $this->getPaymentMethod();

        $encryptData['Delay']   = $paymentMethod->getDelay();
    }
}