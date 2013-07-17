<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Block_Form_Billpay_Abstract
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        //Template
        $this->setTemplate('paymentoperator/form/billpay/default.phtml');
    }

    /**
     * Init the logos for the payment method.
     */
    protected function _initLogos()
    {
        $this->addLogo(
            $this->getSkinUrl('images/paymentoperator/paymentoperator_billpay_default.png'),
            $this->_getHelper()->__('Billpay')
        );
    }

    /**
     * Return the salutation.
     *
     * @return  string
     */
    public function getSalutation()
    {
        return $this->getMethod()->getSalutation();
    }

    /**
     * Return the company name.
     *
     * @return  string
     */
    public function getCompanyName()
    {
        return $this->getMethod()->getCompanyName();
    }

    /**
     * Return the company legal form.
     *
     * @return  string
     */
    public function getCompanyLegalForm()
    {
        return $this->getMethod()->getCompanyLegalForm();
    }

    /**
     * Return all configured legal forms.
     *
     * @return  array
     */
    public function getAvailableCompanyLegalForms()
    {
        return $this->getMethod()->getAvailableCompanyLegalForms();
    }

    /**
     * Return all configured legal forms.
     *
     * @return  array
     */
    public function getAvailableSalutations()
    {
        return $this->getMethod()->getAvailableSalutations();
    }

    /**
     * Return the dob day in the "dd" format.
     *
     * @return  string|null
     */
    public function getBillpayDobDay()
    {
        return $this->getMethod()->getDob('dd');
    }

    /**
     * Return the dob month in the "MM" format.
     *
     * @return  string|null
     */
    public function getBillpayDobMonth()
    {
        return $this->getMethod()->getDob('MM');
    }

    /**
     * Return the dob year in the "yyyy" format.
     *
     * @return  string|null
     */
    public function getBillpayDobYear()
    {
        return $this->getMethod()->getDob('yyyy');
    }

    /**
     * Return true if the method is not available for business customer.
     *
     * @return  boolean
     */
    public function isCompanyAllowed()
    {
        return $this->getMethod()->isCompanyAllowed();
    }

    /**
     * Return the dependences for the input fields.
     *
     * @return  array
     */
    public function getFormDependences()
    {
        $dependences = array();

        //Add company dependencies
        if ($this->isCompanyAllowed()) {
            $dependences[$this->getContainerName(Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_COMPANY_NAME)] = array(
                $this->getFieldName(Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_SALUTATION) => Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::FIRMA,
            );

            $dependences[$this->getContainerName(Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_COMPANY_LEGAL_FORM)] = array(
                $this->getFieldName(Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_SALUTATION) => Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::FIRMA,
            );

            $dependences[$this->getContainerName(Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_DOB)] = array(
                $this->getFieldName(Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_SALUTATION) => array(
                    Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::HERR,
                    Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::FRAU,
                )
            );
        }

        return $dependences;
    }
}