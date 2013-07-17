<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Giropay
    extends Dotsource_Paymentoperator_Model_Payment_Eft
{
    /** Holds the possible values for the value oftMethod*/
    const OTF_METHOD_GIROPAY            = 'giropay';
    const OTF_METHOD_PAGO               = 'PagoOTF';

    protected $_canCapture              = false;

    protected $_canCapturePartial       = false;

    protected $_canRefund               = false;

    protected $_canRefundInvoicePartial = false;

    protected $_canVoid                 = false;


    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_giropay';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paymentoperator/info_giropay';

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_giropay';

    /** Holds the path to the request models */
    protected $_requestModelInfo    = 'paymentoperator/payment_request_giropay_';

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Eft::validate()
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Giropay
     */
    public function validate()
    {
        //Validate the bank data
        parent::validate();

        //Get the payment data
        $info = $this->getInfoInstance();

        //Check active?
        if ('1' != $this->getConfigData('bcn_check') || !$info->hasEftBcn()) {
            return true;
        }

        //Get the giropay check request model
        /* @var $request Dotsource_Paymentoperator_Model_Payment_Request_Giropay_Check */
        $request = $this->_createRequestModel('check', $this->getInfoInstance());

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Get the list from the response
            $activeBanList = $request->getResponseModel()->getResponse()->getData('accibanlist');
            $activeBanList = trim($activeBanList);

            //We need data for processing
            if (empty($activeBanList)) {
                return true;
            }

            //Explode
            $activeBanList = explode(',', $activeBanList);

            //Check if the customer ban is active
            if (!in_array($info->getEftBcn(), $activeBanList)) {
                throw Mage::exception(
                    'Mage_Payment',
                    $this->_getHelper()->__("Your bank don't support this payment method."),
                    $this->getCode().'_eft_bcn'
                );
            }
        }

        return true;
    }


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