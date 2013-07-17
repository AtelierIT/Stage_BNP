<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Connection
{

    /** Holds the connection settings */
    protected $_connectionSettings  = array();


    /**
     * Send a request to paymentoperator.
     *
     * @param Dotsource_Paymentoperator_Model_Payment_Request_Request $request
     */
    public function sendRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request $request)
    {
        //Send the request and get the response
        $response = $this->_send($request);

        //Set the response to the request object
        $request->setResponse($response, false);

        //Log the response
        $this->_getHelper()->Log(
            $request->getResponseModel()->getResponse(),
            "Response ({$request->getRequestFile()})",
            $request->getResponseModel()->getDangerousTags()
        );

        //Log the request
        if ($request->logRequest()) {
            //Log the request as action
            Mage::getModel('paymentoperator/action')->logRequest($request)->save();
        }
    }

    /**
     * Send the request to paymentoperator and save the response.
     *
     * @param Dotsource_Paymentoperator_Model_Payment_Request_Request $request
     */
    protected function _send(Dotsource_Paymentoperator_Model_Payment_Request_Request $request)
    {
        //Only send requests if the paymentoperator payment module is global activated
        if (!$this->_getHelper()->isGlobalActive()) {
            Mage::throwException(
                $this->_getHelper()->__(
                    'The Paymentoperator payment module is global deactivated. Please check our Magento backend.'
                )
            );
        }

        //Get a new client model
        $client = $this->_getClient();

        //Set the request uri
        $client->setUri($this->_getHelper()->getConfiguration()->getBaseUrl()."{$request->getRequestFile()}");

        //Set the post data
        $client->setParameterPost(
            $request->getRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request::REQUEST_AS_ARRAY)
        );

        //Send the request to paymentoperator and get the response
        $response = $client->request(Zend_Http_Client::POST);

        //Check if the response is successful
        if (!$response->isSuccessful()) {
            return null;
        }

        return $response->getBody();
    }

    /**
     * Return an already configured client.
     *
     * @return Zend_Http_Client
     */
    protected function _getClient()
    {
        //Create a new client
        $client = new Zend_Http_Client(
            null,
            array(
                'adapter'      => 'Zend_Http_Client_Adapter_Socket',
                'ssltransport' => 'tls',
                'timeout'      => 15,
            )
        );

        //Return the configured client object
        return $client;
    }

    /**
     * Set the current connection settings data.
     *
     * @param array $field
     * @return Dotsource_Paymentoperator_Model_Connection
     */
    public function setConnectionSettings(array $settings)
    {
        $this->_connectionSettings = $settings;
        return $this;
    }

    /**
     * Return the specified url.
     *
     * @return string
     */
    protected function _getUrl()
    {
        return $this->_getConnectionSettingsField('url');
    }

    /**
     * Return the specified username.
     *
     * @return string
     */
    protected function _getUsername()
    {
        return $this->_getConnectionSettingsField('username');
    }

    /**
     * Return the specified password.
     *
     * @return string
     */
    protected function _getPassword()
    {
        //First step get the password
        $password = $this->_getConnectionSettingsField('password');

        try {
            //Try to decrypt password
            $decrypted = Mage::helper('core')->decrypt($password);

            //Check of valid data
            if (!empty($decrypted)) {
                $password = $decrypted;
            }
        } catch (Exception $e) {
            //Do nothing on exception
        }

        return $password;
    }

    /**
     * Return a specified field from the config settings.
     *
     * @param string $field
     * @return string
     */
    protected function _getConnectionSettingsField($field)
    {
        if (!array_key_exists($field, $this->_connectionSettings)) {
            Mage::throwException(
                $this->_getHelper()->__('No connection setting field with the name is %s specified.', $field)
            );
        }

        //If the data exists return the value
        return $this->_connectionSettings[$field];
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