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

class Dotsource_Paymentoperator_Block_Form_Paypalexpress
    extends Dotsource_Paymentoperator_Block_Form_Paypalstandard
{

    /**
     * Retrieve payment method model
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Paypalexpress
     */
    public function getMethod()
    {
        return Mage::getModel('paymentoperator/payment_paypalexpress');
    }

    /**
     * Retrieves the configuration helper for the paypal.
     *
     * @return  Dotsource_Paymentoperator_Helper_Paypal_Config
     */
    protected function _getPaypalExpressConfig()
    {
        return Mage::helper('paymentoperator/paypal_config');
    }
}