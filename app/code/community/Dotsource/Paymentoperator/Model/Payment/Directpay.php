<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Directpay
    extends Dotsource_Paymentoperator_Model_Payment_Eft
{

    protected $_canCapture              = false;

    protected $_canCapturePartial       = false;

    protected $_canRefund               = false;

    protected $_canRefundInvoicePartial = false;

    protected $_canVoid                 = false;

    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_directpay';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paymentoperator/info_directpay';

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_directpay';

    /** Holds the path to the request models */
    protected $_requestModelInfo    = 'paymentoperator/payment_request_directpay_';

    /**
     * @see Mage_Payment_Model_Method_Abstract::authorize()
     *
     * @param Varien_Object $payment
     * @param unknown_type $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        //Get the request model
        $requestModel = $this->_createRequestModel(__FUNCTION__, $payment);

        //Set the amount
        $requestModel
            ->setAmount($amount);

        //Process the request data and create the redirect url for the payment gateway
        $this->_setOrderPlaceRedirectUrl(
            $this->_getHelper()->getConfiguration()->getBaseUrl().
            $requestModel->getRequestFile().
            '?'.
            $requestModel->getRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request::REQUEST_AS_STRING)
        );
    }
}