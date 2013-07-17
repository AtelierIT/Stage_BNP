<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Session_Billpay
    extends Mage_Core_Model_Session_Abstract
{

    /**
     * Holds the keys for data access in the session.
     */
    const KEY_HASH  = "hash";
    const KEY_RATES = "rates";
    const KEY_PAYID = "payid";


    /**
     * Init the billpay session.
     */
    protected function _construct()
    {
        parent::_construct();

        //Use the website id to create unique website sessions
        $this->init('paymentoperator_billpay_' . Mage::app()->getStore()->getWebsiteId());
    }

    /**
     * Reset and reinit the session with the given hash value.
     *
     * @param   string  $hash
     */
    public function initNewSession($hash)
    {
        $this
            ->clear()
            ->addData(
                array(
                    self::KEY_HASH  => $hash,
                )
            );
    }

    /**
     * Return true if the session hash and the given hash are the same. If
     * given parameter $reinitSessionOnUnequal is true a new session will
     * reinit with the given (new) hash value.
     *
     * @param   string  $hash
     * @param   boolean $reinitSessionOnUnequal
     * @return  boolean
     */
    public function sameHash($hash, $reinitSessionOnUnequal = true)
    {
        //Check for the same hash
        $same = ($hash === $this->_getData(self::KEY_HASH));

        //Check if we should recreate the 3d secure session
        if (!$same && $reinitSessionOnUnequal) {
            $this->initNewSession($hash);
        }

        return $same;
    }

    /**
     * Return true if we have already loaded rates.
     *
     * @return   boolean
     */
    public function hasRates()
    {
        $rates = $this->getRates();
        return $rates && is_array($rates);
    }

    /**
     * Return the rates.
     *
     * @return  array|null
     */
    public function getRates()
    {
        return $this->getData(self::KEY_RATES);
    }

    /**
     * Set all needed data from the response to this session. If the response
     * and all needed data are valid this method return true.
     *
     * @param   Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Authorize $response
     * @return  boolean
     */
    public function setRates(Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Authorize $response)
    {
        //Set the data
        if ($response->hasError() || !$response->getRates() || !$response->getPayid()) {
            return false;
        }

        //Set the data to the session
        $this->addData(
            array(
                self::KEY_RATES => $response->getRates(),
                self::KEY_PAYID => $response->getPayid(),
            )
        );
        return true;
    }

    /**
     * Return the payid from the session.
     *
     * @return  string|null
     */
    public function getPayId()
    {
        return $this->getData(self::KEY_PAYID);
    }
}