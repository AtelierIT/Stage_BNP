<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Block_Form_Klarna_Abstract
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        $this->addLogo(
            $this->getSkinUrl('images/paymentoperator/paymentoperator_klarna.png'),
            $this->_getHelper()->__('Klarna')
        );
    }

    /**
     * Return the gender.
     *
     * @return
     */
    public function getKlarnaGender()
    {
        //Get the gender
        $gender = $this->getInfoData('klarna_gender');

        //If we don't have a gender use the customer gender if it is available
        if (!$gender) {
            if ($this->getMethod()->getOracle()->isGenderMale()) {
                return Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract::GENDER_MALE;
            } else if ($this->getMethod()->getOracle()->isGenderFemale()) {
                return Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract::GENDER_FEMALE;
            }
        }

        return $gender;
    }

    /**
     * Return the dob as Zend_Date.
     *
     * @return Zend_Date || null
     */
    protected function _getKlarnaDob()
    {
        //Get the dob
        $dob = $this->getInfoData('klarna_dob');

        //Get the klarna dob or the customer dob for default value
        if ($dob) {
            $dob = Mage::app()->getLocale()->date($dob, null, null, false);
        } else {
            $dob = $this->getMethod()->getOracle()->getDob();
        }

        //We need valid data
        if ($dob) {
            return $dob;
        }

        return null;
    }

    /**
     * Return the dob day in the "dd" format.
     *
     * @return string || null
     */
    public function getKlarnaDobDay()
    {
        //Get the dob
        $dob = $this->_getKlarnaDob();

        //Return the dob day
        if ($dob) {
            return $dob->toString('dd');
        }

        return null;
    }

    /**
     * Return the dob month in the "MM" format.
     *
     * @return string || null
     */
    public function getKlarnaDobMonth()
    {
        //Get the dob
        $dob = $this->_getKlarnaDob();

        //Return the dob day
        if ($dob) {
            return $dob->toString('MM');
        }

        return null;
    }

    /**
     * Return the dob year in the "yyyy" month.
     *
     * @return string | null
     */
    public function getKlarnaDobYear()
    {
        //Get the dob
        $dob = $this->_getKlarnaDob();

        //Return the dob day
        if ($dob) {
            return $dob->toString('yyyy');
        }

        return null;
    }

    /**
     * Return the klarna ssn.
     *
     * @return string || null
     */
    public function getKlarnaSsn()
    {
        //Get the klarna ssn
        $ssn = $this->getInfoInstance()->getData('klarna_ssn');

        if ($ssn) {
            //Decrypt
            $ssn = $this->getInfoInstance()->decrypt($ssn);

            //Return the ssn
            if ($ssn) {
                return $this->escapeHtml($ssn);
            }
        }

        return null;
    }

    /**
     * Return the klarna max length for the ssn field.
     */
    public function getKlarnaSsnMaxLength()
    {
        return $this->getMethod()->getSsnFieldMaxLength();
    }

    /**
     * Return the klarna annual salary.
     *
     * @return string || null
     */
    public function getKlarnaAnnualSalary()
    {
        //Get the klarna annual salary.
        $annualSalary = $this->getInfoInstance()->getData('klarna_annual_salary');

        if ($annualSalary) {
            //Decrypt
            $annualSalary = $this->getInfoInstance()->decrypt($annualSalary);

            //Return the ssn
            if ($annualSalary) {
                return $this->escapeHtml($annualSalary);
            }
        }

        return null;
    }
}