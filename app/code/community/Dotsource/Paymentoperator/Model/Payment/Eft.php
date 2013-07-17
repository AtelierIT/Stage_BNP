<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Eft
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{

    protected $_canCapturePartial           = false;

    protected $_canRefund                   = false;

    protected $_canRefundInvoicePartial     = false;

    /** Holds the block source path */
    protected $_formBlockType               = 'paymentoperator/form_eft';

    /** Holds the info source path */
    protected $_infoBlockType               = 'paymentoperator/info_eft';

    /** Holds the payment code */
    protected $_code                        = 'paymentoperator_eft';

    /** Holds the path to the request models */
    protected $_requestModelInfo            = 'paymentoperator/payment_request_eft_';


    /**
     * Configure the payment fields.
     */
    public function __construct()
    {
        parent::__construct();

        //Configure the payment fields
        $this->_paymentInformationFields['eft_owner']   =
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;

        $this->_paymentInformationFields['eft_ban_enc'] =
            Dotsource_Paymentoperator_Model_Paymentinformation::ENCRYPTED      |
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;

        $this->_paymentInformationFields['eft_bcn']     =
            Dotsource_Paymentoperator_Model_Paymentinformation::PREFILL        |
            Dotsource_Paymentoperator_Model_Paymentinformation::REQUIRED;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::assignData()
     *
     * @param mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!$data instanceof Varien_Object) {
            $data = new Varien_Object($data);
        }

        $converter  = $this->_getHelper()->getConverter();
        $info       = $this->getInfoInstance();

        //Remove the spaces from the user input
        $ban        = $converter->removeWhitespaces($data->getEftBan());
        $bcn        = $converter->removeWhitespaces($data->getEftBcn());

        $info
            ->setEftOwner($data->getEftOwner())
            ->setEftBanEnc($info->encrypt($ban))
            ->setEftBan4(substr($ban, -3))
            ->setEftBcn($bcn);

        return $this;
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Abstract::canCapture()
     *
     * @return bool
     */
    public function canCapture()
    {
        return $this->_getHelper()->isFrontend() || parent::canCapture();
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Abstract::authorize()
     *
     * @param Varien_Object $payment
     * @param unknown_type $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        parent::authorize($payment, $amount);

        //If we use auth set the ready capture status
        if (Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE
            == $this->getConfigData('payment_action_paymentoperator')
        ) {
            $this->setNewOrderStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::READY_FOR_CAPTURE);
        }

        return $this;
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Abstract::capture()
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        //If we call the capture method from the frontend we can use the
        //the authorize method to capture the payment
        if ($this->_getHelper()->isFrontend()) {
            return $this->authorize($payment, $amount);
        } else {
            return parent::capture($payment, $amount);
        }
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::validate()
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Eft
     */
    public function validate()
    {
        //Do parent stuff first
        parent::validate();

        //Get the data
        $info   = $this->getInfoInstance();

        //Check name
        if (!Zend_Validate::is($info->getEftOwner(), 'NotEmpty')) {
            $msg    = 'Your account holder is invalid.';
            $msg    = $this->_getHelper()->__($msg);
            $code   = "{$this->getCode()}_eft_owner";
            throw Mage::exception('Mage_Payment', $msg, $code);
        }

        //Check ban
        if (!Zend_Validate::is($info->decrypt($info->getEftBanEnc()), 'Digits')) {
            $msg    = 'Your bank account number is invalid.';
            $msg    = $this->_getHelper()->__($msg);
            $code   = "{$this->getCode()}_eft_ban";
            throw Mage::exception('Mage_Payment', $msg, $code);
        }

        //Check bcn
        if (!Zend_Validate::is($info->getEftBcn(), 'Digits')) {
            $msg    = 'Your bank code number is invalid.';
            $msg    = $this->_getHelper()->__($msg);
            $code   = "{$this->getCode()}_eft_bcn";
            throw Mage::exception('Mage_Payment', $msg, $code);
        }

        return $this;
    }

    /**
     * Retrieves the configuration for the payment method.
     *
     * @param   string  $field
     * @param   integer $storeId
     * @return  mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        //Return the right payment action
        if ('payment_action' == $field) {
            switch ($this->getConfigData('payment_action_paymentoperator', $storeId)) {
                case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING:
                    return Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING;
                case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                    return Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE;
            }
        }

        return parent::getConfigData($field, $storeId);
    }
}