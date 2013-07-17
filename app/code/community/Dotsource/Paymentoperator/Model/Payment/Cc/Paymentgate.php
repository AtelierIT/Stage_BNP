<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Cc_Paymentgate
    extends Dotsource_Paymentoperator_Model_Payment_Cc_Abstract
{

    /** Check if we can capture direct from the backend */
    protected $_canBackendDirectCapture     = true;

    /** Holds the block source path */
    protected $_formBlockType               = 'paymentoperator/form_cc_paymentgate';

    /** Holds the info source path */
    protected $_infoBlockType               = 'paymentoperator/info_cc_paymentgate';

    /** Holds the payment code */
    protected $_code                        = 'paymentoperator_cc'; //TODO: Change??

    /** Holds the path to the request models */
    protected $_requestModelInfo            = 'paymentoperator/payment_request_cc_paymentgate_';


    /**
     * Configure the payment fields.
     */
    public function __construct()
    {
        parent::__construct();

        //Configure the payment fields
        $this->_paymentInformationFields['cc_number_enc']   =
        Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY    |
            Dotsource_Paymentoperator_Model_Paymentinformation::ENCRYPTED      |
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;

        $this->_paymentInformationFields['cc_number']       = 0;

        $this->_paymentInformationFields['cc_last4']        =
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;

        $this->_paymentInformationFields['cc_exp_year']     =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY    |
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;

        $this->_paymentInformationFields['cc_exp_month']    =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY    |
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;

        $this->_paymentInformationFields['cc_type']         =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY    |
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::assignData()
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!$data instanceof Varien_Object) {
            $data = new Varien_Object($data);
        }

        //Get the info instance
        $info = $this->getInfoInstance();

        if ($this->isPrefillinformationActive()
            && $data->getData("{$this->getCode()}_cc_prefill_information")
            && $this->hasLastPaymentInformation()
        ) {
            //Add the previous payment data
            $info
                ->addData($this->getLastPaymentInformation()->getData())
                ->setCcPrefillInformation(1);
        } else {
            //Clear the prefill flag
            $info->setCcPrefillInformation(0);

            //Clear the info data
            $prefillFields = $this->getPaymentInformationModel()->getFeaturedPaymentFields(
                Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL
            );
            foreach ($prefillFields as $field) {
                $info->setData($field, null);
            }
        }

        return $this;
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
        $requestModel = $this->_createRequestModel('authorize', $payment);

        //Set the amount
        $requestModel
            ->setAmount($amount);

        //Process the request data and create the redirect url for the payment gateway
        $this->_setOrderPlaceRedirectUrl(
            $this->_getHelper()->getConfiguration()->getBaseUrl().
            $requestModel->getRequestFile().
            '?'.
            $requestModel->getRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request::REQUEST_AS_STRING),
            true,
            $this->_getHelper()->__('Credit Card Payment Gateway')
        );
    }

    /**
     * Return true if the prefill information is active for cc.
     *
     * @return boolean
     */
    public function isPrefillinformationActive($storeId = null)
    {
        return $this->isUsePseudoDataActive($storeId)
            && parent::isPrefillinformationActive($storeId);
    }
}