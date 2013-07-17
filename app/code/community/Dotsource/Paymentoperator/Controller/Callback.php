<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Controller_Callback
    extends Mage_Core_Controller_Front_Action
{

    /** Holds the key for the session params */
    const SESSION_KEY_PARAM             = 'ctmsid';


    /** Holds if the redirect is an javascript redirect */
    protected $_javaScriptRedirect      = false;

    /** Holds if we should use the custom session id */
    protected $_usePreloadCustomSession = true;


    /**
     * Override the pre dispatch method for load a custom session.
     *
     * @return Dotsource_Paymentoperator_Controller_Callback
     */
    public function preDispatch()
    {
        //Global session processing active?
        if ($this->_usePreloadCustomSession) {
            //Get a custom session id
            $sessionId = $this->getRequest()->getParam(self::SESSION_KEY_PARAM, null);

            //Custom session id is available?
            if (null !== $sessionId) {
                //Set a pre session id this session id can rewrite with the get session parameter
                session_id($sessionId);
            }
        }

        //Now do all the parent stuff
        return parent::preDispatch();
    }


    /**
     * Set the redirect to the success page.
     */
    protected function _successRedirect()
    {
        $this->_redirect('checkout/onepage/success');
    }


    /**
     * Set the redirect to the back page.
     */
    protected function _backRedirect()
    {
        $this->_redirect('checkout/');
    }


    /**
     * Set the redirect to the failure page.
     */
    protected function _failureRedirect()
    {
        $this->_redirect('checkout/cart/');
    }


    /**
     * Send a 404 http status code.
     */
    protected function _notifyActionError()
    {
        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
        $this->getResponse()->setHttpResponseCode(404);
    }


    /**
     * @see Mage_Core_Controller_Varien_Action::_redirect()
     *
     * @param string $path
     * @param array $arguments
     */
    protected function _redirect($path, $arguments = array())
    {
        if ($this->_javaScriptRedirect) {
            $this->_javascriptRedirect($path, $arguments);
        } else {
            parent::_redirect($path, $arguments);
        }
    }


    /**
     * JavaScript redirect can use for redirect from an iframe.
     *
     * @param string $url
     * @param array $arguments
     */
    protected function _javascriptRedirect($path, $arguments = array())
    {
        //TODO: Refactore as template
        //Build the url
        $url = Mage::getUrl($path, $arguments);

        //Build the js content
        $js = '';
        $js .=  '<html>';
        $js .=      '<body>';
        $js .=          '<script type="text/javascript">';
        $js .=          '/* <![CDATA[ */';
        $js .=              'top.location.href = "' . $url . '";';
        $js .=          '/* ]]> */';
        $js .=          '</script>';
        $js .=          '<a href="' . $url . '" target="_parent">';
        $js .=              $this->_getHelper()->__("If your not getting redirected click here.");
        $js .=          '</a>';
        $js .=      '</body>';
        $js .=  '</html>';

        //Set the javascript redirect
        Mage::app()->getResponse()->setBody($js);
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