<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Response
    extends Dotsource_Paymentoperator_Object
{

    /**
     * Holds the referencing request.
     * @var Dotsource_Paymentoperator_Model_Payment_Request_Request|null
     */
    protected $_requestObject            = null;

    /** Holds the paymentoperator object */
    protected $_responseObject          = null;

    /** Holds the user data object */
    protected $_userDataObject          = null;

    /** Holds the response data as string */
    protected $_responseString          = null;

    /** Holds the error status */
    protected $_errorStatus             = array('failed');

    /**
     * Holds dangerous tags how should not displayed.
     * @var array
     */
    protected $_dangerousTags           = array('pcnr');


    /**
     * Fill the data container from the current response object.
     *
     * @param   string|array    $responseString
     * @param   boolean         $encrypted
     * @return  Dotsource_Paymentoperator_Model_Payment_Response_Response
     */
    public function setResponse($responseString, $forcedEncryption = true)
    {
        //Get the right data
        if (is_string($responseString)) {
            $response   = $this->_parseQueryString($responseString);
        } elseif (is_array($responseString)) {
            $response   = array_change_key_case($responseString, CASE_LOWER);
        } else {
            throw new Exception('Only string and array are supported as given response.');
        }

        //Check if we need
        if (array_key_exists('data', $response)) {
            $response = $this->getEncryptor()->decrypt($response['data']);
        } elseif ($forcedEncryption) {
            throw new Exception('Only encrypted response are allowed to pass.');
        }

        //Holds the response string
        $this->_responseObject  = new Dotsource_Paymentoperator_Object();
        $this->getResponse()
            ->setUseUndecodeParameters($this->getUseUndecodeParameters())
            ->setData($response);

        //Reset the user data
        $this->_userDataObject = null;

        return $this;
    }

    /**
     * Set the referencing request.
     *
     * @param   Dotsource_Paymentoperator_Model_Payment_Request_Request    $request
     */
    public function setRequest(Dotsource_Paymentoperator_Model_Payment_Request_Request $request)
    {
        $this->_requestObject = $request;
    }

    /**
     * Return the referencing request object.
     *
     * @return  Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function getRequest()
    {
        return $this->_requestObject;
    }

    /**
     * Check if the response is well formed.
     *
     * @return boolean
     */
    public function isResponseValid()
    {
        return $this->getResponse() instanceof Dotsource_Paymentoperator_Object
            && $this->getResponse()->hasData();
    }

    /**
     * Return true if the response as an error.
     *
     * @return boolean
     */
    public function hasError()
    {
        if ($this->isResponseValid()) {
            //Check status for an error state
            $status = $this->getResponse()->getStatus();
            $status = trim($status);

            //We need an non empty code and a non error status
            if (empty($status) || in_array(strtolower($status), $this->_errorStatus)) {
                return true;
            }

            //Check code
            $code = $this->getResponse()->getCode();
            $code = trim($code);

            //The only good code is 0
            //check for numeric and if the code is numeric check for zero
            return !(is_numeric($code) && intval($code) == 0);
        }

        return true;
    }

    /**
     * Indicates if the response is pending.
     *
     * @return  boolean
     */
    public function isPending()
    {
        if (!$this->hasError()) {
            $status = $this->getResponse()->getStatus();
            $status = trim($status);
            return 'pending' === strtolower($status);
        }

        return false;
    }

    /**
     * Indicates if the response is ok.
     *
     * @return  boolean
     */
    public function isOk()
    {
        if (!$this->hasError()) {
            $status = $this->getResponse()->getStatus();
            $status = trim($status);
            return 'ok' === strtolower($status);
        }
        return false;
    }

    /**
     * Return the response from the current object.
     *
     * @return Dotsource_Paymentoperator_Object
     */
    public function getResponse()
    {
        return $this->_responseObject;
    }

    /**
     * Return the user data object.
     *
     * @return Dotsource_Paymentoperator_Object || null
     */
    public function getUserData()
    {
        //already created
        if (null === $this->_userDataObject) {
            $this->_userDataObject = new Dotsource_Paymentoperator_Object();

            //check if we have some user data to parse
            if (!$this->getResponse()->isDataEmpty('userdata')) {
                $this->_userDataObject->setData(base64_decode($this->getResponse()->getData('userdata')));
            }

            //Set the user data in the response
            $this->getResponse()->setData('userdata', $this->_userDataObject);
        }

        return $this->_userDataObject;
    }

    /**
     * Return true if the request has user data.
     *
     * @return boolean
     */
    public function hasUserData()
    {
        return $this->getUserData()->isEmpty();
    }

    /**
     * @see Dotsource_Paymentoperator_Object::toString()
     *
     * @param string $noNeed
     * @return string
     */
    public function toString($noNeed = null)
    {
        //If we have an response object we return the string from the request object
        if ($this->isResponseValid()) {
            return $this->getResponse()->toString();
        }

        return "";
    }

    /**
     * Return dangerous tags who should not saved.
     *
     * @return array
     */
    public function getDangerousTags()
    {
        return $this->_dangerousTags;
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