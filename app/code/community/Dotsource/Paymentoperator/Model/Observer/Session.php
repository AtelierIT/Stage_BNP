<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Session
{

    /**
     * If the checkout session getting cleared we need to unset the data
     * in the paymentoperator session.
     *
     * @param Varien_Event_Observer $observer
     */
    public function clearCustomerSession(Varien_Event_Observer $observer)
    {
        //Clear the paymentoperator session
        Mage::getSingleton('paymentoperator/session')->clearSession();

        //Clear the iframe url from the checkout session
        $this->_getHelper()->getCheckoutSession()->unsIframeUrl();
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