<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 27.04.2010 14:12:50
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Block_Form_Paypalstandard
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        //Template
        $this->setTemplate('paymentoperator/form/paypalstandard.phtml');
    }

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        $this->addLogo(
            $this->getSkinUrl('images/paymentoperator/paymentoperator_paypal.png'),
            $this->_getHelper()->__('PayPal')
        );
    }

    /**
     * Retrieve payment method model
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Paypalstandard
     */
    public function getMethod()
    {
        return Mage::getModel('paymentoperator/payment_paypalstandard');
    }

    /**
     * Retrieves the configuration helper for the paypal.
     *
     * @return  Dotsource_Paymentoperator_Helper_Paypal_Config
     */
    protected function _getPaypalConfig()
    {
        return Mage::helper('paymentoperator/paypal_config');
    }
}