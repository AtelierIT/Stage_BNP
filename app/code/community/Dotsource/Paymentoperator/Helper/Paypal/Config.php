<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 27.04.2010 14:41:01
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Helper_Paypal_Config
{

    /**
     * Get PayPal "mark" image URL supposed to be used on payment methods
     * selection.
     * $staticSize is applicable for static images only.
     *
     * @param   string  $localeCode
     * @param   float   $orderTotal
     * @param   string  $pal
     * @param   string  $staticSize
     * @return  string
     */
    public function getPaymentMarkImageUrl($localeCode, $orderTotal = null, $pal = null, $staticSize = null)
    {
        return $this->_getPaypalConfig()->getPaymentMarkImageUrl(
            $localeCode, $orderTotal, $pal, $staticSize
        );
    }


    /**
     * Retrieves the localized "What Is PayPal" url.
     *
     * This method is supposed to be used with "mark" as popup window.
     *
     * @param   Mage_Core_Model_Locale  $locale
     */
    public function getPaymentMarkWhatIsPaypalUrl(Mage_Core_Model_Locale $locale = null)
    {
        return $this->_getPaypalConfig()->getPaymentMarkWhatIsPaypalUrl($locale);
    }


    /**
     * Retrieves the payment form logo image url.
     *
     * @param   string  $localeCode
     * @return  string
     */
    public function getPaymentFormLogoUrl($localeCode)
    {
        return $this->_getPaypalConfig()->getPaymentFormLogoUrl($localeCode);
    }


    /**
     * Express checkout shortcut pic URL getter
     * PayPal will ignore "pal", if there is no total amount specified
     *
     * @param   string  $localeCode
     * @param   float   $orderTotal
     * @param   string  $pal encrypted summary about merchant
     * @return  string
     */
    public function getExpressCheckoutShortcutImageUrl($localeCode, $orderTotal = null, $pal = null)
    {
        return $this->_getPaypalConfig()->getExpressCheckoutShortcutImageUrl(
            $localeCode, $orderTotal, $pal
        );
    }


    /**
     * Retrieves the PayPal configuration model.
     *
     * @return  Mage_Paypal_Model_Config
     */
    protected function _getPaypalConfig()
    {
        return Mage::getSingleton('paypal/config');
    }
}