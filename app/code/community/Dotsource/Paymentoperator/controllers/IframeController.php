<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_IframeController
    extends Dotsource_Paymentoperator_Controller_Callback
{

    /**
     * Show the iframe in the checkout process.
     */
    public function showAction()
    {
        if ($this->hasIframeUrl()) {
            //load the layout
            $this->loadLayout();

            //Get the block and set the iframe url
            $this->getLayout()
                ->getBlock('checkout_iframe')
                ->setIframeUrl($this->getIframeUrl())
                ->setIframeTitle($this->getIframeTitle());

            $this->renderLayout();
        } else {
            $this->_noIframeUrlError();
        }
    }


    /**
     * Check if we have a iframe url (source).
     *
     * @return boolean
     */
    protected function hasIframeUrl()
    {
        //Get the current iframe source url.
        $url = $this->getIframeUrl();

        //check for non empty string
        return !empty($url) && is_string($url);
    }


    /**
     * Check if we have a iframe title.
     *
     * @return boolean
     */
    protected function hasIframeTitle()
    {
        //Get the current iframe source url.
        $title = $this->getIframeTitle();

        //check for non empty string
        return !empty($title) && is_string($title);
    }


    /**
     * Return the iframe url.
     *
     * @return string
     */
    protected function getIframeUrl()
    {
        return $this->_getHelper()
            ->getCheckoutSession(Dotsource_Paymentoperator_Helper_Data::FRONTEND)
            ->getIframeUrl();
    }


    /**
     * Return the iframe title.
     *
     * @return string
     */
    protected function getIframeTitle()
    {
        $title = $this->_getHelper()
            ->getCheckoutSession(Dotsource_Paymentoperator_Helper_Data::FRONTEND)
            ->getIframeTitle();

        //Check for an title
        if (!empty($title)) {
            return $title;
        }

        return $this->_getHelper()->__('Payment Gateway');
    }


    /**
     * Holds the error message if no iframe url is available.
     */
    protected function _noIframeUrlError()
    {
        //Build the message
        $msg = 'The iframe url to the PaymentGate is missing. '.
            'Please try again to pay or choose another payment method.';

        //Add a error message
        $this->_getHelper()->getCheckoutSession()->addError(
            $this->_getHelper()->__($msg)
        );

        //Redirect to cart to show the error message
        $this->_redirect('checkout/cart/');
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