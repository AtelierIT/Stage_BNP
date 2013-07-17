<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Session_3dsecure
    extends Mage_Core_Model_Session_Abstract
{

    /** Three states for the enrollment check */
    const ENROLLMENT_STATUS_UNCHECKED           = null;
    const ENROLLMENT_STATUS_ENROLLED            = 1;
    const ENROLLMENT_STATUS_NOT_ENROLLED        = 0;

    /** Holds the tow authentication states */
    const AUTHENTICATION_STATE_UMPERFORMED      = null;
    const AUTHENTICATION_STATE_SUCCESSFULL      = 1;
    const AUTHENTICATION_STATE_FAILURE          = 0;

    /** Holds keys for the data access */
    const KEY_HASH                              = "hash";
    const KEY_ENROLLMENT_STATUS                 = "enrollment_status";
    const KEY_AUTHENTICATION_STATUS             = "authentication_status";

    /** Keys from the initial paymentoperator request */
    const KEY_ACS_URL                           = "acs_url";
    const KEY_PA_REQUEST                        = "pa_request";
    const KEY_MD                                = "md";
    const KEY_TERM_URL                          = "term_url";
    const KEY_PSEUDO_CC_NUMBER                  = "pseudo_cc_number";

    /** Keys for the response data */
    const KEY_MID                               = "mid";
    const KEY_PAY_ID                            = "pay_id";
    const KEY_TRANS_ID                          = "trans_id";
    const KEY_PA_RESPONSE                       = "pa_response";


    /**
     * Init the 3d secure session.
     */
    protected function _construct()
    {
        parent::_construct();

        //Use the website id to create unique website sessions
        $this->init('paymentoperator_3dsecure_' . Mage::app()->getStore()->getWebsiteId());
    }

    /**
     * Reset the 3d secure session and set a new hash value.
     *
     * @param string $hash
     */
    public function initNew3DSecureSession($hash)
    {
        $this
            ->clear()
            ->addData(
                array(
                    self::KEY_HASH                  => $hash,
                    self::KEY_ENROLLMENT_STATUS     => self::ENROLLMENT_STATUS_UNCHECKED,
                    self::KEY_AUTHENTICATION_STATUS => self::AUTHENTICATION_STATE_UMPERFORMED,
                )
            );
    }

    /**
     * Return true if the session hash and the given hash are the same.
     *
     * @param   string  $hash
     * @param   boolean $reinitOnError
     * @return  boolean
     */
    public function sameHash($hash, $reinitSessionOnUnequal = true)
    {
        //Check for the same hash
        $same = ($hash === $this->_getData(self::KEY_HASH));

        //Check if we should recreate the 3d secure session
        if (!$same && $reinitSessionOnUnequal) {
            $this->initNew3DSecureSession($hash);
        }

        return $same;
    }

    /**
     * Set all needed data from the given response model to the session
     * This method return the new enrollment status. The return value
     * ENROLLMENT_STATUS_UNCHECKED indicates an error.
     *
     * @param   Dotsource_Paymentoperatorextension_Model_Payment_Response_Cc_Pci_3dsecure_Enrollmentcheck $response
     * @return  int|null
     */
    public function setEnrollmentData(
        /*Dotsource_Paymentoperatorextension_Model_Payment_Response_Cc_Pci_3dsecure_Enrollmentcheck*/ $response
    )
    {
        if ($response->isEnrolled()) {
            //Card is enrolled
            $this->addData(
                array(
                    self::KEY_ACS_URL           => (string) $response->getAscUrl(),
                    self::KEY_PA_REQUEST        => (string) $response->getPaRequest(),
                    self::KEY_MD                => (string) $response->getMerchantData(),
                    self::KEY_PAY_ID            => (string) $response->getPayId(),
                    self::KEY_TERM_URL          => (string) $response->getTermUrl(),
                    self::KEY_ENROLLMENT_STATUS => self::ENROLLMENT_STATUS_ENROLLED,
                )
            );
        } elseif ($response->isNotEnrolled()) {
            //Card is not enrolled
            $this->addData(
                array(
                    self::KEY_PAY_ID            => (string) $response->getPayId(),
                    self::KEY_ENROLLMENT_STATUS => self::ENROLLMENT_STATUS_NOT_ENROLLED,
                )
            );
        } else {
            //Error in response
            $this->setData(self::KEY_ENROLLMENT_STATUS, self::ENROLLMENT_STATUS_UNCHECKED);
        }

        return $this->_getEnrollmentStatus();
    }

    /**
     * Return true if we have all data for an authentication.
     *
     * @return  boolean
     */
    public function hasAllAuthenticationData()
    {
        $validate = new Zend_Filter_Input(
            array(),
            array(
                self::KEY_ACS_URL                   => array(),
                self::KEY_PA_REQUEST                => array(),
                self::KEY_MD                        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::KEY_TERM_URL                  => array(),
            ),
            $this->getAuthenticationData(),
            array(
                Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                Zend_Filter_Input::ALLOW_EMPTY  => false
            )
        );

        return $validate->isValid();
    }

    /**
     * Return an array of data that need for the authentication.
     *
     * @return  array
     */
    public function getAuthenticationData()
    {
        return array(
            self::KEY_ACS_URL       => $this->_getData(self::KEY_ACS_URL),
            self::KEY_PA_REQUEST    => $this->_getData(self::KEY_PA_REQUEST),
            self::KEY_MD            => $this->_getData(self::KEY_MD),
            self::KEY_TERM_URL      => $this->_getData(self::KEY_TERM_URL),
        );
    }

    /**
     * Set all needed data to the session.
     *
     * @param Mage_Core_Controller_Request_Http $request
     * @return int|null
     */
    public function setAuthenticationData(Mage_Core_Controller_Request_Http $request)
    {
        //Get all data from the given request
        $responseData = array(
            self::KEY_PAY_ID                => $request->getQuery('PayID'),
            self::KEY_MID                   => $request->getPost('MID'),
            self::KEY_MD                    => $request->getPost('MD'),
            self::KEY_TRANS_ID              => $request->getPost('TransID'),
            self::KEY_PA_RESPONSE           => $request->getPost('PaRes'),
            self::KEY_PA_REQUEST            => $request->getPost('PaReq'),
        );

        //Check the preconditions to set the data
        if ($responseData[self::KEY_PA_REQUEST] === $this->_getData(self::KEY_PA_REQUEST)
            && $responseData[self::KEY_PAY_ID] === $this->_getData(self::KEY_PAY_ID)
        ) {
            //Validate the data from the request
            $validate = new Zend_Filter_Input(
                array(),
                array(
                    self::KEY_MID                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    self::KEY_MD                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                    self::KEY_PAY_ID                => array(),
                    self::KEY_TRANS_ID              => array(Zend_Filter_Input::ALLOW_EMPTY => true), //FIXME: Needed?
                    self::KEY_PA_RESPONSE           => array(),
                    self::KEY_PA_REQUEST            => array(),
                ),
                $responseData,
                array(
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Zend_Filter_Input::ALLOW_EMPTY  => false
                )
            );

            //Only store valid data in the session
            if ($validate->isValid()) {
                $this->addData($responseData);

                //Dispatch a event to do some additional validations
                Mage::dispatchEvent(
                    'paymentoperator_valid_3d_secure_authentication_request',
                    array(
                        'session'   => $this,
                    )
                );
            }
        }

        return $this->_getAuthenticationStatus();
    }

    /**
     * Return true if the card is already enrolled.
     * ENROLLMENT_STATUS_UNCHECKED:     not already checked
     * ENROLLMENT_STATUS_ENROLLED:      credit card IS enrolled
     * ENROLLMENT_STATUS_NOT_ENROLLED:  credit card IS NOT enrolled
     *
     * @return int|null
     */
    protected function _getEnrollmentStatus()
    {
        return $this->_getData(self::KEY_ENROLLMENT_STATUS);
    }

    /**
     * Return the authentication status.
     * AUTHENTICATION_STATE_UMPERFORMED:    no authentication was performed
     * AUTHENTICATION_STATE_SUCCESSFULL:    was successfully performed
     * AUTHENTICATION_STATE_FAILURE:        authentication was failed
     *
     * @return int|null
     */
    protected function _getAuthenticationStatus()
    {
        return $this->_getData(self::KEY_AUTHENTICATION_STATUS);
    }

    /**
     * Return true if the current credit card is enrolled.
     *
     * @return boolean
     */
    public function isEnrolled()
    {
        return self::ENROLLMENT_STATUS_ENROLLED === $this->_getEnrollmentStatus();
    }

    /**
     * Return true if the card is not enrolled.
     *
     * @return boolean
     */
    public function isUnenrolled()
    {
        return self::ENROLLMENT_STATUS_NOT_ENROLLED === $this->_getEnrollmentStatus();
    }

    /**
     * Return true if the enrollment was not performed.
     *
     * @return boolean
     */
    public function isEnrollmentCheckNeeded()
    {
        return self::ENROLLMENT_STATUS_UNCHECKED === $this->_getEnrollmentStatus();
    }

    /**
     * Return true if the 3d secure session is successfully authenticated.
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return self::AUTHENTICATION_STATE_SUCCESSFULL === $this->_getAuthenticationStatus();
    }

    /**
     * Return true if no previous authentication is available.
     *
     * @return boolean
     */
    public function isAuthenticationNeeded()
    {
        return !$this->isUnenrolled() && !$this->isAuthenticated();
    }
}