<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 30.04.2010 16:31:18
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Model_Payment_Mpass
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{

    protected $_canRefund               = false;

    protected $_canRefundInvoicePartial = false;

    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_mpass';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paymentoperator/info_mpass';

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_mpass';

    /** Holds the path to the request models */
    protected $_requestModelInfo    = 'paymentoperator/payment_request_mpass_';

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
        $requestModel->setAmount($amount);

        //Process the request data and create the redirect url for the payment gateway
        $this->_setOrderPlaceRedirectUrl(
            $this->_getHelper()->getConfiguration()->getBaseUrl().
            $requestModel->getRequestFile().
            '?'.
            $requestModel->getRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request::REQUEST_AS_STRING)
        );
    }
}