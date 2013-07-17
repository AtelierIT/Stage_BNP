<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Block_Form_Cc_Abstract
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    /**
     * Holds a list of county depend logos.
     * @var array
     */
    protected static $_countyDependLogos = array(
        'dankort'               => array('DK'),
        'carte_bleue_nationale' => array('FR'),
    );

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        //Data we need
        $availableLogos = Mage::getModel('paymentoperator/system_config_source_logos')->toOptionArray();
        $selectedLogos  = array_flip(explode(',', $this->getMethod()->getConfigData('logos')));

        //Add the selected logos
        foreach ($availableLogos as $singleLogo) {
            $value = $singleLogo['value'];

            //Add the logo iof the preconditions are ok
            if (isset($selectedLogos[$value]) && $this->_canShowLogo($value)) {
                //Add the logo
                $this->addLogo(
                    $this->getSkinUrl("images/paymentoperator/paymentoperator_cc/{$singleLogo['file']}"),
                    $this->_getHelper()->__($singleLogo['label'])
                );
            }
        }
    }

    /**
     * Return true if the logo can show.
     *
     * @param $data
     * @return boolean
     */
    protected function _canShowLogo($logoKey)
    {
        $billingAddress = $this->getMethod()->getOracle()->getBillingAddress();
        return !isset(self::$_countyDependLogos[$logoKey])
            || (isset(self::$_countyDependLogos[$logoKey])
                && $billingAddress
                && in_array(strtoupper($billingAddress->getCountryId()), self::$_countyDependLogos[$logoKey])
        );
    }

    /**
     * Return true if prefill information is active.
     *
     * @return boolean
     */
    public function isPrefillinformationActive()
    {
        return (boolean) $this->getMethod()->isPrefillinformationActive();
    }

    /**
     * Return true if the customer has a previous payment information.
     *
     * @return boolean
     */
    public function hasPreviousPaymentInformation()
    {
        return (boolean) $this->getMethod()->hasLastPaymentInformation();
    }

    /**
     * Return the formated text for the use of the previous payment information.
     *
     * @return string
     */
    public function getUsePreviousCCText()
    {
        //Get the payment information
        $lastPaymentInformations = $this->getMethod()->getLastPaymentInformation();
        return $this->__(
            'Use the previous credit card xxxx-%s',
            $lastPaymentInformations->getData('cc_last4')
        );
    }

    /**
     * Return the text for the new cc card.
     *
     * @return string
     */
    public function getUseNewCCText()
    {
        return $this->__('Use new credit card');
    }

    /**
     * Return true if the previous payment information should use.
     *
     * @return boolean
     */
    public function getIsUsePreviousPaymentInformation()
    {
        $ccPrefillPayment = $this->getMethod()->getInfoInstance()->getCcPrefillInformation();
        return $ccPrefillPayment || null === $ccPrefillPayment;
    }

    /**
     * Retrieve payment configuration object.
     *
     * @return  Mage_Payment_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

    /**
     * Retrieve availables credit card types.
     *
     * @return  array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * Retrieve credit card expire months.
     *
     * @return  array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] =  $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            $this->setData('cc_months', $months);
        }
        return $months;
    }

    /**
     * Retrieve credit card expire years.
     *
     * @return  array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            $years = array(0 => $this->__('Year')) + $years;
            $this->setData('cc_years', $years);
        }
        return $years;
    }

    /**
     * Retrive has verification configuration.
     *
     * @return  boolean
     */
    public function hasVerification()
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv');
            if (null === $configData) {
                return true;
            }
            return (bool)$configData;
        }
        return true;
    }

    /**
     * Whether switch/solo card type available.
     *
     * @return  boolean
     */
    public function hasSsCardType()
    {
        $availableTypes =$this->getMethod()->getConfigData('cctypes');
        if ($availableTypes && in_array('SS', explode(',', $availableTypes))) {
            return true;
        }
        return false;
    }

    /**
     * Solo/switch card start year.
     *
     * @return  array
     */
    public function getSsStartYears()
    {
        $years = array();
        $first = date('Y');

        for ($index = 5; $index >= 0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = array(0 => $this->__('Year')) + $years;
        return $years;
    }

    /**
     * Render block HTML.
     *
     * @return  string
     */
    protected function _toHtml()
    {
        Mage::dispatchEvent(
            'payment_form_block_to_html_before',
            array(
                'block' => $this
            )
        );
        return parent::_toHtml();
    }
}