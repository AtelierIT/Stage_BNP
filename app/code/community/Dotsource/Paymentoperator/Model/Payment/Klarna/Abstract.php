<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract
    extends Dotsource_Paymentoperator_Model_Payment_Abstract
{

    /** Holds the male code */
    const GENDER_MALE   = 'm';

    /** Holds the female code */
    const GENDER_FEMALE = 'f';


    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_klarna';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paymentoperator/info_klarna';


    /**
     * Retrieve value of klarna action.
     *
     * @return int|string
     */
    abstract public function getKlarnaAction();


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

        $info = $this->getInfoInstance();

        if ($this->isDobNeeded()) {
            $dobFull = sprintf(
                '%s-%s-%s',
                $data->getKlarnaDobYear(),
                $data->getKlarnaDobMonth(),
                $data->getKlarnaDobDay()
            );

            $info->setKlarnaDob(
                Mage::app()->getLocale()->date($dobFull, null, null, false)->toString('yyyy-MM-dd')
            );
        }

        //Set the gender if needed
        if ($this->isGenderNeeded()) {
            $info->setKlarnaGender($data->getKlarnaGender());
        } else {
            $info->setKlarnaGender(null);
        }

        //Set the ssn if needed
        if ($this->isSsnNeeded() || $this->isCommercialRegisterNumberNeeded()) {
            $info->setKlarnaSsn($info->encrypt($data->getKlarnaSsn()));
        } else {
            $info->setKlarnaSsn(null);
        }

        //Set the annual salary if needed
        if ($this->isAnnualSalaryNeeded()) {
            $info->setKlarnaAnnualSalary($info->encrypt($data->getKlarnaAnnualSalary()));
        } else {
            $info->setKlarnaAnnualSalary(null);
        }

        return $this;
    }


    /**
     * @see Mage_Payment_Model_Method_Abstract::validate()
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract
     */
    public function validate()
    {
        parent::validate();

        $this->_validateGender();
        $this->_validateDob();
        $this->_validateSocialSecurityNumber();
        $this->_validateCommercialRegisterNumber();
        $this->_validateAnnualSalary();

        return $this;
    }


    /**
     * Check the customer selected gender.
     * Throws an Mage_Payment_Exception exception on error.
     */
    protected function _validateGender()
    {
        if (!$this->isGenderNeeded()) {
            return;
        }

        $info        = $this->getInfoInstance();
        $haystack    = array('haystack' => array(self::GENDER_MALE, self::GENDER_FEMALE));

        if (!Zend_Validate::is($info->getKlarnaGender(), 'inArray', $haystack)) {
            $msg    = $this->_getHelper()->__('Your gender is invalid.');
            $field  = "{$this->getCode()}_gender";
            throw Mage::exception('Mage_Payment', $msg, $field);
        }
    }


    /**
     * Check if DOB is right.
     *
     */
    protected function _validateDob()
    {
        if (!$this->isDobNeeded()) {
            return;
        }

        $info = $this->getInfoInstance();
        if (!Zend_Validate::is($info->getKlarnaDob(), 'date', array('format' => 'yyyy-MM-dd'))) {
            $msg    = $this->_getHelper()->__('Your date of birth is invalid.');
            $field  = "{$this->getCode()}_dob_full";
            throw Mage::exception('Mage_Payment', $msg, $field);
        }
    }


    /**
     * Check if SSN is right.
     *
     * Check only if customer is a private person AND
     *      country is made of SE,FI,DK,NO
     */
    protected function _validateSocialSecurityNumber()
    {
        if (!$this->isSsnNeeded()) {
            return;
        }

        //Data we need
        $info           = $this->getInfoInstance();
        $ssn            = $info->getKlarnaSsn();
        $neededStrLen   = $this->getSsnFieldMaxLength();

        //Decrypt the data
        if ($ssn) {
            $ssn = $info->decrypt($ssn);
        }

        if (!Zend_Validate::is($ssn, 'digits')) {
            $msg = $this->_getHelper()->__('Only use digits.');
            $field  = "{$this->getCode()}_ssn";
            throw Mage::exception('Mage_Payment', $msg, $field);
        } elseif (!Zend_Validate::is($ssn, 'stringLength', array('min' => $neededStrLen, 'max' => $neededStrLen))) {
            $msg    = $this->_getHelper()->__('Your Social Security Number must be %s digits long.', $neededStrLen);
            $field  = "{$this->getCode()}_ssn";
            throw Mage::exception('Mage_Payment', $msg, $field);
        }
    }


    /**
     * Check the commercial register number.
     */
    protected function _validateCommercialRegisterNumber()
    {
        if (!$this->isCommercialRegisterNumberNeeded()) {
            return;
        }

        //Data we need
        $info   = $this->getInfoInstance();
        $ssn    = $info->getKlarnaSsn();

        //If we have data we decypt these
        if ($ssn) {
            $ssn = $info->decrypt($ssn);
        }

        if (!Zend_Validate::is($ssn, 'digits')) {
            $msg = $this->_getHelper()->__('Only use digits.');
            $field  = "{$this->getCode()}_ssn";
            throw Mage::exception('Mage_Payment', $msg, $field);
        } elseif (!Zend_Validate::is($ssn, 'stringLength', array('min' => 4, 'max' => 5))) {
            $msg    = $this->_getHelper()->__('Your Commercial Register Number must be 4 to 5 digits long.');
            $field  = "{$this->getCode()}_ssn";
            throw Mage::exception('Mage_Payment', $msg, $field);
        }
    }


    /**
     * Check if Annual Salary is right.
     *
     * Only check if country is DK.
     */
    protected function _validateAnnualSalary()
    {
        if (!$this->isAnnualSalaryNeeded()) {
            return;
        }

        //Data we need
        $info           = $this->getInfoInstance();
        $annualSalary   = $info->getKlarnaAnnualSalary();

        //If we have data we decypt these
        if ($annualSalary) {
            $annualSalary = $info->decrypt($annualSalary);
        }

        if (!Zend_Validate::is($annualSalary, 'notEmpty')) {
            $msg    = $this->_getHelper()->__('Required entry.');
            $field  = "{$this->getCode()}_annual_salary";
            throw Mage::exception('Mage_Payment', $msg, $field);
        } elseif (!Zend_Validate::is($annualSalary, 'digits')) {
            $msg    = $this->_getHelper()->__('Only use digits.');
            $field  = "{$this->getCode()}_annual_salary";
            throw Mage::exception('Mage_Payment', $msg, $field);
        }
    }


    /**
     * Return billing address from quote.
     *
     * @return Mage_Sales_Model_Quote_Address || Mage_Sales_Model_Order_Address
     */
    protected function _getBillingAddress()
    {
        return $this->getOracle()->getBillingAddress();
    }


    /**
     * Check if customer is private or company.
     *
     * @return boolean
     */
    public function isBusinessCustomer()
    {
        $companyData = trim($this->_getBillingAddress()->getCompany());

        return (boolean)$companyData;
    }


    /**
     * Check if Social Security Number is needed for billing country.
     *
     * @return boolean
     */
    public function isSsnNeeded()
    {
        if ($this->isBusinessCustomer()) {
            return false;
        }

        $currentCountryId = strtoupper($this->_getBillingAddress()->getCountryId());
        $neededCountryIds = array('SE', 'FI', 'DK', 'NO');

        return array_key_exists($currentCountryId, array_flip($neededCountryIds));
    }


    /**
     * Return true if a commercial register number is needed.
     *
     * @return boolean
     */
    public function isCommercialRegisterNumberNeeded()
    {
        return $this->isBusinessCustomer();
    }


    /**
     * Check if Annual Salary is needed for billing country.
     *
     * @return boolean
     */
    public function isAnnualSalaryNeeded()
    {
         return !$this->isBusinessCustomer()
             && 'DK' === strtoupper($this->_getBillingAddress()->getCountryId());
    }


    /**
     * Return true if the gender is needed.
     *
     * @return boolean
     */
    public function isGenderNeeded()
    {
        return !$this->isBusinessCustomer();
    }


    /**
     * Return true if the gender is needed.
     *
     * @return boolean
     */
    public function isDobNeeded()
    {
        return !$this->isBusinessCustomer();
    }


    /**
     * Return true if the reference param is needed.
     *
     * @return boolean
     */
    public function isReferenceNeeded()
    {
        return $this->isBusinessCustomer();
    }


    /**
     * Return the gender label.
     *
     * @param $value
     * @param $missingLabel
     * @return string || mixed
     */
    public function getGenderLabel($value, $missingLabel = '-')
    {
        //Translate
        switch ($value) {
            case self::GENDER_MALE:
                return $this->_getHelper()->__('Male');
            case self::GENDER_FEMALE:
                return $this->_getHelper()->__('Female');
        }

        //Return the missing label
        return $missingLabel;
    }


    /**
     * Return a needed ssn label.
     *
     * @return string || mixed
     */
    public function getSsnLabel($missingLabel = '-')
    {
        if ($this->isSsnNeeded()) {
            return $this->_getHelper()->__('Social Security Number');
        } elseif ($this->isCommercialRegisterNumberNeeded()) {
            return $this->_getHelper()->__('Commercial Register Number');
        }

        return $missingLabel;
    }


    /**
     * Return the max length for the ssn field. If this function return null
     * no ssn is needed.
     *
     * @param $countryId
     * @return int || null
     */
    public function getSsnFieldMaxLength($countryId = null)
    {
        if ($this->isBusinessCustomer()) {
            return 5;
        } else {
            //Get the country id  if needed
            if (null === $countryId) {
                $countryId = $this->getOracle()->getBillingAddress()->getCountryId();
            }

            switch (strtoupper($countryId)) {
                case 'SE':
                case 'FI':
                case 'DK':
                    return 4;
                case 'NO':
                    return 5;
            }
        }

        return null;
    }


    /**
     * Klarna is the only payment that need to set a amount.
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
}