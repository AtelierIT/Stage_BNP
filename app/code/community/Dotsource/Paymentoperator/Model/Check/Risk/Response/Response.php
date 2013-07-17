<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Response_Response
    extends Dotsource_Paymentoperator_Model_Payment_Response_Response
{

    /** Holds the mapping of the "aktion" values */
    protected $_mappingValues = array(
        'rot'   => 'red',
        'gelb'  => 'yellow',
        'gruen' => 'green'
    );


    /**
     * Return true if the response as an error.
     *
     * @return boolean
     */
    public function hasError()
    {
        return !$this->isResponseValid();
    }


    /**
     * Check if the response is valid. This function will also valid the
     * "aktion" value.
     *
     * @return boolean
     */
    public function isResponseValid()
    {
        //Is the parent logic true?
        if (!parent::isResponseValid()) {
            return false;
        }

        //The response is only valid if the field "aktion" is in the mapping
        $aktion = $this->getActionValue();
        return $aktion && isset($this->_mappingValues[$aktion]);
    }


    /**
     * Return the normalized paymentoperator "aktion" value.
     *
     * @return string
     */
    public function getActionValue()
    {
        return strtolower($this->getResponse()->getAktion());
    }


    /**
     * Return the mapped "aktion" value.
     *
     * @return string || null
     */
    public function getMappedActionValue()
    {
        //Get the aktion value
        $aktion = $this->getActionValue();

        //Return the mapped value if exists
        if (isset($this->_mappingValues[$aktion])) {
            return $this->_mappingValues[$aktion];
        }

        return null;
    }


    /**
     * Return true if the value "aktion" is green.
     *
     * @return boolean
     */
    public function isGreen()
    {
        return 'gruen' === $this->getActionValue();
    }


    /**
     * Return true if the value "aktion" is yellow.
     *
     * @return boolean
     */
    public function isYellow()
    {
        return 'gelb' === $this->getActionValue();
    }


    /**
     * Return true if the value "aktion" is red.
     *
     * @return boolean
     */
    public function IsRed()
    {
        return 'rot' === $this->getActionValue();
    }
}