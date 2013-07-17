<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{

    /**
     * This is the order status if a billpay payment is ready for capture.
     */
    const READY_FOR_BILLPAY_CAPTURE                 = 'billpay_ready_for_capture';

    /**
     * Keys for the salutation.
     */
    const HERR                                      = 'herr';
    const FRAU                                      = 'frau';
    const FIRMA                                     = 'firma';

    /**
     * Keys for the data access.
     */
    const KEY_SALUTATION                            = 'billpay_salutation';
    const KEY_COMPANY_NAME                          = 'billpay_company_name';
    const KEY_COMPANY_LEGAL_FORM                    = 'billpay_company_legal_form';
    const KEY_DOB                                   = 'billpay_dob';
    const KEY_DOB_DD                                = 'billpay_dob_dd';
    const KEY_DOB_MM                                = 'billpay_dob_mm';
    const KEY_DOB_YYYY                              = 'billpay_dob_yyyy';
    const KEY_EFT_OWNER                             = 'billpay_eft_owner';
    const KEY_EFT_BAN_ENC                           = 'billpay_eft_ban_enc';
    const KEY_EFT_BAN3                              = 'billpay_eft_ban3';
    const KEY_EFT_BCN                               = 'billpay_eft_bcn';
    const KEY_GENERAL_TERMS                         = 'billpay_general_terms';
    const KEY_TERM                                  = 'billpay_term';

    /**
     * Holds data keys for billpay eft payment receive.
     */
    const KEY_BILLPAY_EFT_OWNER                     = 'billpay_receiver_owner';
    const KEY_BILLPAY_EFT_BAN_ENC                   = 'billpay_receiver_ban_enc';
    const KEY_BILLPAY_EFT_BCN                       = 'billpay_receiver_bcn';
    const KEY_BILLPAY_EFT_BANK_NAME                 = 'billpay_receiver_bank_name';
    const KEY_BILLPAY_EFT_INVOICE_REF               = 'billpay_receiver_invoice_ref';
    const KEY_BILLPAY_EFT_INVOICE_DATE              = 'billpay_receiver_invoice_date';

    /**
     * Holds the billpay transaction id
     */
    const KEY_BILLPAY_TRANSACTION_ID                = 'billpay_transaction_id';


    /**
     * Change the default model path to billpay main request folder.
     */
    protected $_requestModelDefault                 = 'paymentoperator/payment_request_billpay_';

    /**
     * We can only capture the complete order.
     * @var boolean
     */
    protected $_canCapturePartial                   = false;

    /**
     * We can also capture without a previous authorization.
     * @var boolean
     */
    protected $_canBackendDirectCapture             = true;

    /**
     * Billpay has no time depends on void a capture.
     * @var boolean
     */
    protected $_ignoreDateByVoidCapture             = true;

    /**
     * Holds a flag that indicates if the payment method is not available for
     * business customer.
     * @var boolean
     */
    protected $_isCompanyAllowed                    = true;

    /**
     * Caches the available salutations for the payment method.
     * @var array|null
     */
    protected $_cachedAvailableSalutations          = null;


    /**
     * Returns the billpay action.
     *
     * @return  string
     */
    abstract function getBillpayAction();


    /**
     * Has the real implementation of the authorize process.
     *
     * @param   Varien_Object                                       $payment
     * @param   float                                               $amount
     * @param   boolean                                             $setTransactionInformsations
     * @return  Dotsource_Paymentoperator_Model_Payment_Response_Response
     */
    abstract protected function _billpayAuthorize(
        Varien_Object $payment,
        $amount,
        $setTransactionInformsations = true
    );

    /**
     * Has the real implementation of the capture process.
     *
     * @param   Varien_Object                                       $payment
     * @param   float                                               $amount
     * @param   Mage_Sales_Model_Order_Payment_Transaction          $authorizationTransaction
     * @return  Dotsource_Paymentoperator_Model_Payment_Response_Response
     */
    abstract protected function _billpayCapture(
        Varien_Object $payment,
        $amount,
        Mage_Sales_Model_Order_Payment_Transaction $authorizationTransaction = null
    );


    /**
     * Configure the payment fields.
     */
    public function __construct()
    {
        parent::__construct();

        //Configure the payment fields
        $this->_paymentInformationFields[self::KEY_SALUTATION]          =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY;

        $this->_paymentInformationFields[self::KEY_COMPANY_NAME]        =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY;

        $this->_paymentInformationFields[self::KEY_COMPANY_LEGAL_FORM]  =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY;

        $this->_paymentInformationFields[self::KEY_DOB]                 =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY;

        $this->_paymentInformationFields[self::KEY_EFT_OWNER]           =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY;

        $this->_paymentInformationFields[self::KEY_EFT_BAN_ENC]         =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY    |
            Dotsource_Paymentoperator_Model_Paymentinformation::ENCRYPTED;

        $this->_paymentInformationFields[self::KEY_EFT_BCN]             =
            Dotsource_Paymentoperator_Model_Paymentinformation::PAYMENT_KEY;
    }

    /**
     * Extend the default isAvailable method.
     *
     * @param   Mage_Sales_Model_Quote|null $quote
     */
    public function isAvailable($quote = null)
    {
        //Do the parent check first
        $available = parent::isAvailable($quote);
        if (!$available) {
            return $available;
        }

        //Check if we have unresolved depends
        if ($this->getDepends()) {
            return false;
        }

        //If the payment is not allowed for business but the current customer
        //is a business customer so we disable the payment method
        if ($available && $quote && !$this->isCompanyAllowed()) {
            /* @var $oracle Dotsource_Paymentoperator_Model_Oracle_Type_Order */
            if (!isset($oracle)) {
                $oracle = Mage::getModel('paymentoperator/oracle_type_order')->setModel($quote);
            }
            $available  = !$oracle->getBillingAddress()->getCompany();
        }

        //If we don't allow a different billing address we need to check if the addresses are equal
        if ($available && $quote && !$this->getConfigData('differentdeliveryaddress')) {
            //At this point we are not able to use the oracle from this method
            if (!isset($oracle)) {
                $oracle = Mage::getModel('paymentoperator/oracle_type_order')->setModel($quote);
            }
            $available  = $oracle->isShippingAddressEqualToBillingAddress();
        }

        return $available;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::assignData()
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract
     */
    public function assignData($data)
    {
        if (!$data instanceof Varien_Object) {
            $data = new Varien_Object($data);
        }

        //Holds the converter
        $converter = $this->_getHelper()->getConverter();

        //Set all data to the info instance
        $this->getInfoInstance()
            ->setData(self::KEY_SALUTATION, $data->getData($this->getFieldName(self::KEY_SALUTATION)))
            ->setData(self::KEY_COMPANY_NAME, $data->getData($this->getFieldName(self::KEY_COMPANY_NAME)))
            ->setData(self::KEY_COMPANY_LEGAL_FORM, $data->getData($this->getFieldName(self::KEY_COMPANY_LEGAL_FORM)))
            ->setData(self::KEY_GENERAL_TERMS, $data->getData($this->getFieldName(self::KEY_GENERAL_TERMS)))
            ->setData(self::KEY_TERM, $data->getData($this->getFieldName(self::KEY_TERM)));

        //Handle encryption payment
        $ban = $converter->removeWhitespaces($data->getData($this->getFieldName(self::KEY_EFT_BAN_ENC)));
        if ($ban) {
            $this->getInfoInstance()
                ->setData(self::KEY_EFT_OWNER, $data->getData($this->getFieldName(self::KEY_EFT_OWNER)))
                ->setData(self::KEY_EFT_BAN_ENC, $this->getInfoInstance()->encrypt($ban))
                ->setData(self::KEY_EFT_BAN3, substr($ban, -3))
                ->setData(self::KEY_EFT_BCN, $converter->removeWhitespaces($data->getData($this->getFieldName(self::KEY_EFT_BCN))));
        } else {
            $this->getInfoInstance()
                ->setData(self::KEY_EFT_OWNER, null)
                ->setData(self::KEY_EFT_BAN_ENC, null)
                ->setData(self::KEY_EFT_BAN3, null)
                ->setData(self::KEY_EFT_BCN, null);
        }

        //Generate the DOB
        $day    = $data->getData($this->getFieldName(self::KEY_DOB_DD));
        $month  = $data->getData($this->getFieldName(self::KEY_DOB_MM));
        $year   = $data->getData($this->getFieldName(self::KEY_DOB_YYYY));
        $this->getInfoInstance()->setData(self::KEY_DOB, "{$year}-{$month}-{$day}");

        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::validate()
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract
     */
    public function validate()
    {
        parent::validate();

        //Holds the parameter for the validation
        $parameter = array(
            self::KEY_SALUTATION            => $this->getInfoInstance()->getData(self::KEY_SALUTATION),
            self::KEY_COMPANY_NAME          => $this->getInfoInstance()->getData(self::KEY_COMPANY_NAME),
            self::KEY_COMPANY_LEGAL_FORM    => $this->getInfoInstance()->getData(self::KEY_COMPANY_LEGAL_FORM),
            self::KEY_DOB                   => $this->getInfoInstance()->getData(self::KEY_DOB),
            self::KEY_EFT_OWNER             => $this->getInfoInstance()->getData(self::KEY_EFT_OWNER),
            self::KEY_EFT_BAN_ENC           => $this->getInfoInstance()->getData(self::KEY_EFT_BAN_ENC),
            self::KEY_EFT_BAN3              => $this->getInfoInstance()->getData(self::KEY_EFT_BAN3),
            self::KEY_EFT_BCN               => $this->getInfoInstance()->getData(self::KEY_EFT_BCN),
            self::KEY_GENERAL_TERMS         => $this->getInfoInstance()->getData(self::KEY_GENERAL_TERMS),
            self::KEY_TERM                  => $this->getInfoInstance()->getData(self::KEY_TERM),
        );

        //Decrypt parameter for validation
        if ($parameter[self::KEY_EFT_BAN_ENC]) {
            $parameter[self::KEY_EFT_BAN_ENC] = $this->getInfoInstance()->decrypt(
                $parameter[self::KEY_EFT_BAN_ENC]
            );
        }

        //Validate the salutation first this field has validation dependence
        if (!in_array($parameter[self::KEY_SALUTATION], array_keys($this->getAvailableSalutations()))) {
            throw Mage::exception(
                'Mage_Payment',
                $this->_getHelper()->__("Your salutation is invalid."),
                $this->getFieldName(self::KEY_SALUTATION)
            );
        }

        //Validate the parameters
        $this->_validate($parameter, $this->_getValidationRules());

        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::authorize()
     *
     * @param   Varien_Object               $payment
     * @param   float                       $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $this->_billpayAuthorize($payment, $amount);
        $this->setNewOrderStatus(self::READY_FOR_BILLPAY_CAPTURE);
        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::capture()
     *
     * @param   Varien_Object               $payment
     * @param   float                       $amount
     * @return  Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $authorizationTransaction   = null;
        $oracle                     = $this->getOracle();
        $needAuthorizeCall          = $oracle->isQuote()
            || ($oracle->isOrder() && $oracle->getModel()->getStatus() !== self::READY_FOR_BILLPAY_CAPTURE);

        //Do the authorization first if needed
        if ($needAuthorizeCall) {
            $response = $this->_billpayAuthorize($payment, $amount, false);

            //Create a fake authorization transaction for the capture
            /* @var $authorizationTransaction Mage_Sales_Model_Order_Payment_Transaction */
            $authorizationTransaction = Mage::getModel('sales/order_payment_transaction');
            $authorizationTransaction
                ->setTxnId($response->getResponse()->getXid())
                ->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)
                ->setIsClosed(0)
                ->setAdditionalInformation('payid', $response->getResponse()->getPayid());
        }

        $this->_billpayCapture($payment, $amount, $authorizationTransaction);
        return $this;
    }

    /**
     * Return the validations rules.
     *
     * @return  array
     */
    protected function _getValidationRules(array $validationRules = array())
    {
        //Add validation of the company fields if needed
        if ($this->isCompanyAllowed() && $this->isCompany()) {
            //Do a not empty check
            $validationRules[self::KEY_COMPANY_NAME]         = array(
                'NotEmpty',
                Zend_Filter_Input::MESSAGES => array(
                    $this->_getHelper()->__("Your company name is invalid."),
                )
            );

            //Check if the company legal form is available in the haystack
            $validationRules[self::KEY_COMPANY_LEGAL_FORM]   = array(
                array('InArray', array('haystack' => array_keys($this->getAvailableCompanyLegalForms()))),
                Zend_Filter_Input::MESSAGES => array(
                    $this->_getHelper()->__("Your company legal form is invalid."),
                )
            );
        }

        //Add validation of the dob if needed
        if ($this->isDobNeeded()) {
            $validationRules[self::KEY_DOB]  = array(
                array('Date', array('format' => 'yyyy-MM-dd')),
                Zend_Filter_Input::MESSAGES => array(
                    $this->_getHelper()->__("Your date of birth is invalid."),
                )
            );
        }

        //Check if the terms and conditions are accepted
        $validationRules[self::KEY_GENERAL_TERMS]  = array(
            array('Identical', array('token' => 'on')),
            Zend_Filter_Input::MESSAGES => array(
                $this->_getHelper()->__("You need to accept the terms and conditions to continue the checkout."),
            )
        );

        return $validationRules;
    }

    /**
     * Validate the given parameter with the given validation rules. If
     * the validation fail the method throw an Mage_Payment_Exception object.
     *
     * @param array $parameters
     * @param array $validatorRules
     */
    protected function _validate(array $parameters, array $validatorRules)
    {
        $validator = new Zend_Filter_Input(
            array('*' => 'StringTrim'),
            $validatorRules,
            $parameters,
            array(
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                Zend_Filter_Input::ALLOW_EMPTY  => false,
            )
        );

        //Throw the first error as exception
        if (!$validator->isValid()) {
            $messages           = $validator->getMessages();
            $messageContainer   = current($messages);
            $field              = key($messages);
            list(, $message)    = each($messageContainer);
            throw Mage::exception('Mage_Payment', $message, $this->getFieldName($field));
        }
    }

    /**
     * Check if the current customer is a company.
     *
     * @return  boolean
     */
    public function isCompany()
    {
        $salutation = $this->getInfoInstance()->getData(self::KEY_SALUTATION);

        //Fallback
        if (!$salutation) {
            return (boolean) $this->getCompanyName();
        }

        return self::FIRMA === $salutation;
    }

    /**
     * Return true if the DOB is needed.
     *
     * @return  boolean
     */
    public function isDobNeeded()
    {
        return !$this->isCompany();
    }

    /**
     * Returns the customer salutation.
     *
     * @return  string
     */
    public function getSalutation()
    {
        $title = $this->getInfoInstance()->getData(self::KEY_SALUTATION);

        //Fallback
        if (!$title) {
            if ($this->isCompany()) {
                $title = self::FIRMA;
            } else {
                $title = $this->_getSalutationFromPrefix();
            }
        }

        return $title;
    }

    /**
     * Return the salutation from the selected prefix.
     *
     * @param   string      $prefix
     * @return  string|null
     */
    protected function _getSalutationFromPrefix($prefix = null)
    {
        //Get the default source for prefix
        if (null === $prefix) {
            $prefix = $this->getOracle()->getPrefix();
        }

        //Check for non empty prefix
        if ($prefix) {
            //Holds the mapping from the prefix to the salutation
            static $mapping = null;
            if (null === $mapping) {
                $mapping = array(
                    self::HERR => array(
                        'herr',
                        'hr',
                        'hr.',
                        'mister',
                        'mr',
                        'mr.',
                        'sir',
                        'signor',
                        'sig',
                        'sig.',
                        'gentleman',
                        'gentiluomo',
                        'padrone',
                        'monsieur',
                        'm',
                        'm.',
                    ),
                    self::FRAU => array(
                        'frau',
                        'fräulein',
                        'fr',
                        'fr.',
                        'miss',
                        'ms',
                        'ms.',
                        'mrs',
                        'mrs.',
                        'signora',
                        'sig.ra',
                        'donna',
                        'la signora',
                        'madame',
                        'mme',
                        'mademoiselle',
                        'mlle',
                        'mle',
                    )
                );
            }

            //Try find the prefix in the set
            $prefix = trim(strtolower($prefix));
            foreach ($mapping as $saluation => $prefixSet) {
                if (in_array($prefix, $prefixSet)) {
                    return $saluation;
                }
            }
        }

        return null;
    }

    /**
     * Return the company name.
     *
     * @return  string|null
     */
    public function getCompanyName()
    {
        $companyName = $this->getInfoInstance()->getData(self::KEY_COMPANY_NAME);

        //Fallback
        if (!$companyName) {
            $companyName = $this->getOracle()->getBillingAddress()->getCompany();
        }

        return $companyName;
    }

    /**
     * Return the company legal form.
     *
     * @return  string|null
     */
    public function getCompanyLegalForm()
    {
        $legalForm = $this->getInfoInstance()->getData(self::KEY_COMPANY_LEGAL_FORM);

        //Fallback
        if (!$legalForm) {
            $companyName            = trim($this->getCompanyName());
            $configuredLegalForms   = $this->getAvailableCompanyLegalForms();

            foreach ($configuredLegalForms as $key => $value) {
                //Skip the misc legal form
                if ($key === 'misc') {
                    continue;
                }

                //Get the sub string from the company name in lenght of the
                //value and one extra space (whitespace expected)
                $companyNameEnd = substr($companyName, -(strlen($value) + 1));
                if (strtolower($companyNameEnd) === strtolower(" $value")) {
                    return $key;
                }
            }
        }

        return $legalForm;
    }

    /**
     * Return the customer dob. Its also possible to format the date with
     * the format parameter. If a format is given the date will return as
     * string.
     *
     * @param   string $format
     * @return  Zend_Date|string|null
     */
    public function getDob($format = null)
    {
        $dob = $this->getInfoInstance()->getData(self::KEY_DOB);

        //Convert to zend date if the dob value is valid or use a fallback
        if ($dob) {
            $dob = Mage::app()->getLocale()->date($dob, null, null, false);
        } else {
            $dob = $this->getOracle()->getDob();
        }

        //Return the formatted string
        if ($format && $dob) {
            return $dob->toString($format);
        }

        return $dob;
    }

    /**
     * Return the eft owner. If no eft owner already set the first and last name
     * from the billing address will return.
     *
     * @return  string
     */
    public function getEftOwner()
    {
        $owner = $this->getInfoInstance()->getData(self::KEY_EFT_OWNER);

        if (!$owner) {
            $billingAddress = $this->getOracle()->getBillingAddress();
            if ($billingAddress && $billingAddress->hasData()) {
                $owner = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            }
        }

        return $owner;
    }

    /**
     * Return the customer bank account number (ban). If the given
     * value $decrypt is true the ban will return decrypted.
     *
     * @param   boolean     $decrypt
     * @return  string|null
     */
    public function getEftBan($decrypt = true)
    {
        $ban = $this->getInfoInstance()->getData(self::KEY_EFT_BAN_ENC);

        if ($ban && $decrypt) {
            $ban = $this->getInfoInstance()->decrypt($ban);
        }

        return $ban;
    }

    /**
     * Return the last 3 digits of the customer bank account number (ban).
     *
     * @return  string|null
     */
    public function getEftBan3()
    {
        return $this->getInfoInstance()->getData(self::KEY_EFT_BAN3);
    }

    /**
     * Return the customer bank code number.
     *
     * @return  string|null
     */
    public function getEftBcn()
    {
        return $this->getInfoInstance()->getData(self::KEY_EFT_BCN);
    }

    /**
     * Return all configures legal forms for company.
     *
     * @return  array
     */
    public function getAvailableCompanyLegalForms()
    {
        static $forms = null;
        if (null === $forms) {
            $forms              = array();
            $configuredForms    = @unserialize($this->getConfigData('legal_forms'));
            if ($configuredForms && is_array($configuredForms)) {
                foreach ($configuredForms as $legalFormSet) {
                    $forms[$legalFormSet['key']] = $this->_getHelper()->__($legalFormSet['value']);
                }
            }
        }

        return $forms;
    }

    /**
     * Return all available salutations.
     *
     * @return  array
     */
    public function getAvailableSalutations()
    {
        if (null === $this->_cachedAvailableSalutations) {
            $this->_cachedAvailableSalutations = array(
                self::HERR  => 'Herr',
                self::FRAU  => 'Frau',
            );

            //Check if we also need to add the company
            if ($this->isCompanyAllowed()) {
                $this->_cachedAvailableSalutations[self::FIRMA] = 'Firma';
            }
        }

        return $this->_cachedAvailableSalutations;
    }

    /**
     * Return the salutation label from the given key.
     *
     * @param   string          $key
     * @param   mixed           $missingLabel
     * @return  string|mixed
     */
    public function getSalutationLable($key = null, $missingLabel = '-')
    {
        //Get the current salutation if no one given
        if (null === $key) {
            $key = $this->getSalutation();
        }

        //Resolve the label
        $salutations = $this->getAvailableSalutations();
        if (isset($salutations[$key])) {
            return $salutations[$key];
        }

        return $missingLabel;
    }

    /**
     * Return the customer selected term.
     *
     * @return  string|null
     */
    public function getTerm()
    {
        return $this->getInfoInstance()->getData(self::KEY_TERM);
    }

    /**
     * Return the billpay transaction id.
     *
     * @return  string|null
     */
    public function getBillpayTransactionId()
    {
        return $this->getInfoInstance()->getData(self::KEY_BILLPAY_TRANSACTION_ID);
    }

    /**
     * Return the eft receiver owner.
     *
     * @return  string|null
     */
    public function getEftReceiverOwner()
    {
        return $this->getInfoInstance()->getData(self::KEY_BILLPAY_EFT_OWNER);
    }

    /**
     * Return the eft receiver ban.
     *
     * @param   boolean     $decrypt
     * @return  string|null
     */
    public function getEftReceiverBan($decrypt = true)
    {
        $ban = $this->getInfoInstance()->getData(self::KEY_BILLPAY_EFT_BAN_ENC);

        if ($ban && $decrypt) {
            $ban = $this->getInfoInstance()->decrypt($ban);
        }

        return $ban;
    }

    /**
     * Return the eft receiver bcn.
     *
     * @return  string|null
     */
    public function getEftReceiverBcn()
    {
        return $this->getInfoInstance()->getData(self::KEY_BILLPAY_EFT_BCN);
    }

    /**
     * Return the eft bank name.
     *
     * @return  string|null
     */
    public function getEftReceiverBankName()
    {
        return $this->getInfoInstance()->getData(self::KEY_BILLPAY_EFT_BANK_NAME);
    }

    /**
     * Return the eft invoice reference.
     *
     * @return  string|null
     */
    public function getEftReceiverInvoiceReference()
    {
        return $this->getInfoInstance()->getData(self::KEY_BILLPAY_EFT_INVOICE_REF);
    }

    /**
     * Return the eft invoice date.
     *
     * @return  string|null
     */
    public function getEftReceiverInvoiceDate()
    {
        return $this->getInfoInstance()->getData(self::KEY_BILLPAY_EFT_INVOICE_DATE);
    }

    /**
     * Return true if the payment method is not available for business
     * customers.
     *
     * @return  boolean
     */
    public function isCompanyAllowed()
    {
        return $this->_isCompanyAllowed;
    }

    /**
     * Return the depends. If depends are not comply the array
     * contains error messages.
     *
     * @return  array
     */
    public function getDepends()
    {
        $messages = $this->_getHelper()->getDepends()->isPrefixRequired();
        return $messages;
    }

    /**
     * Return the payment delay.
     *
     * @return  int
     */
    public function getDelay()
    {
        return (int) $this->getConfigData('delay');
    }

    /**
     * Billpay need a amount for the reverse process.
     *
     * @param   Dotsource_Paymentoperator_Model_Payment_Request_Request    $request
     * @param   Varien_Object                                       $payment
     * @@param  Mage_Sales_Model_Order_Invoice                      $invoice
     */
    protected function _setVoidAmount(
        Dotsource_Paymentoperator_Model_Payment_Request_Request    $request,
        Varien_Object                                       $payment,
        Mage_Sales_Model_Order_Invoice                      $invoice
    )
    {
        $cancelModel = $this->getCancelProcessModel($this->getOracle()->getModel());
        $request->setAmount($cancelModel->getRefundableAmountFromInvoice($invoice));
    }

    /**
     * Returns the billpay session.
     *
     * @return  Dotsource_Paymentoperator_Model_Session_Billpay
     */
    public function getBillpaySession()
    {
        return Mage::getSingleton('paymentoperator/session_billpay');
    }
}