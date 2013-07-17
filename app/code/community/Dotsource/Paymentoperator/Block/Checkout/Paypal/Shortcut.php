<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 17.05.2010 10:27:46
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Block_Checkout_Paypal_Shortcut
    extends Mage_Core_Block_Template
{


    /**
     * Retreives the paypal express chechout url.
     *
     * @return  string
     */
    public function getCheckoutUrl()
    {
        return $this->getUrl('paymentoperator/callback_paypalexpress/start');
    }


    /**
     * Get checkout button image url.
     *
     * @return string
     */
    public function getImageUrl()
    {
        return Mage::getModel(
            'paypal/express_checkout',
            array(
                'quote'  => Mage::getSingleton('checkout/session')->getQuote(),
                'config' => $this->_getMethod()->getConfig(),
            )
        )->getCheckoutShortcutImageUrl();
    }


    /**
     * Check whether method is available and render HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_getHelper()->isGlobalActive(true)) {
            $method = $this->_getMethod();
            if ($method && $method->getConfigData('active')) {
                return parent::_toHtml();
            }
        }

        return '';
    }


    /**
     * Retrieve payment method model.
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Paypalexpress
     */
    protected function _getMethod()
    {
        return Mage::getModel('paymentoperator/payment_paypalexpress');
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}