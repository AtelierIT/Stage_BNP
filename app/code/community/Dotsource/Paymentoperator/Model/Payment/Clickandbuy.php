<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 12.01.2011 17:03:30
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Clickandbuy
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{
    protected $_canCapture              = false;
    protected $_canCapturePartial       = false;

    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = false;

    protected $_code                    = 'paymentoperator_click_and_buy';
    protected $_formBlockType           = 'paymentoperator/form_clickandbuy';
    protected $_infoBlockType           = 'paymentoperator/info_clickandbuy';
    protected $_requestModelInfo        = 'paymentoperator/payment_request_clickandbuy_';

    /**
     * Use authorize as a gateway by setting params to url.
     *
     * @param Varien_Object $payment
     * @param float         $amount
     */
    public function authorize(Varien_Object $payment, $amount)
    {
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
