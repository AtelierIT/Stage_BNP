<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Rewrite_Checkout_Onepage_Payment_Methods
    extends Mage_Checkout_Block_Onepage_Payment_Methods
{

    /** Holds if we need to show terms and conditions */
    protected static $_forceShowTermsAndConditions  = null;

    /** Holds the risk model */
    protected $_riskModel                           = null;

    /** Holds the value if the terms and conditions should show */
    protected $_showTermsAndConditions              = null;


    /**
     * Show the terms and conditions if needed.
     *
     * @return string
     */
    public function _toHtml()
    {
        //Show the terms and conditions if available
        if ($this->_showTermsAndConditions()) {
            $block  = $this->_getRiskAgreementBlockInstance();

            //Check for a block instance
            if ($block) {
                $html   = $block->toHtml();

                if ($html) {
                    return $html;
                }
            }
        }

        return parent::_toHtml();
    }


    /**
     * Specify a agreement template for the checkout payment method step.
     *
     * @param $block
     * @param $template
     */
    public function setRiskAgreementBlock($block, $template)
    {
        $this
            ->setRiskAgreementBlockName($block)
            ->setRiskAgreementTemplateName($template);
    }


    /**
     * Return the risk agreement block who was specified
     * with the setRiskAgreementBlock method.
     *
     * @return Mage_Checkout_Block_Agreements || null
     */
    protected function _getRiskAgreementBlockInstance()
    {
        //Try to get a already created block instance
        $instance = $this->getRiskAgreementBlockInstance();

        //Instance already created?
        if (null === $instance) {
            $block      = $this->getRiskAgreementBlockName();
            $template   = $this->getRiskAgreementTemplateName();

            if ($block && $template) {
                //Create the instance if all data available
                $instance = Mage::app()->getLayout()
                    ->createBlock($block)
                    ->setTemplate($template);
            } else {
//                Mage::throwException(
//                    'Missing risk agreement block information. Use setRiskAgreementBlock-Method.'
//                );
            }

            //Set the instance
            $this->setRiskAgreementBlockInstance($instance);
        }

        return $instance;
    }


    /**
     * Return true if we need to show the terms and conditions.
     *
     * @return boolean
     */
    protected function _showTermsAndConditions()
    {
        //Get the force parameter
        $force = $this->_getForceShowTermsAndConditionsValue();

        //Use the force value first
        if (is_bool($force)) {
            return $force;
        }

        //Check if we need to show the terms and conditions
        if (null === $this->_showTermsAndConditions) {
            $this->_showTermsAndConditions = $this->_getRiskModel()->isRiskCheckAtPaymentsMethods()
                && $this->_getRiskModel()->isAvailable()    //Is risk check available?
                && !$this->_getRiskModel()->hasResponse();  //No valid previous response available?
        }

        return $this->_showTermsAndConditions;
    }


    /**
     * Return the risk model
     *
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Risk
     */
    protected function _getRiskModel()
    {
        if (null === $this->_riskModel) {
            //Init the risk model
            $this->_riskModel = Mage::getModel('paymentoperator/check_risk_risk')->init($this->getQuote());
        }

        return $this->_riskModel;
    }


    /**
     * Return the force parameter.
     *
     * @return boolean || null
     */
    protected function _getForceShowTermsAndConditionsValue()
    {
        return self::$_forceShowTermsAndConditions;
    }


    /**
     * Set a force show terms and conditions.
     *
     * @param boolean $flag
     * @return Dotsource_Paymentoperator_Block_Rewrite_Checkout_Onepage_Payment_Methods
     */
    public function setForceShowTermsAndConditionsValue($flag)
    {
        self::$_forceShowTermsAndConditions = (boolean) $flag;
        return $this;
    }

    /**
     * Returns the logo html.
     *
     * @param   Mage_Payment_Model_Method_Abstract  $paymentMethod
     * @return  string
     */
    public function getLogoHtml(Mage_Payment_Model_Method_Abstract $paymentMethod)
    {
        /* @var $form Dotsource_Paymentoperator_Block_Form_Abstract */
        if (($form = $this->getChild("payment.method.{$paymentMethod->getCode()}"))
            && $form instanceof Dotsource_Paymentoperator_Block_Form_Abstract
        ) {
            return $form->getLogoHtml();
        }

        return '';
    }
}