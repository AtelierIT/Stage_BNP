<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Logos
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    const MASTERCARD                  = 'paymentoperator_mastercard.png';
    const MASTERCARD_SECURE_CODE      = 'paymentoperator_mastercard_secure_code.png';
    const VISA                        = 'paymentoperator_visa.png';
    const VERIFIED_BY_VISA            = 'paymentoperator_verified_by_visa.png';
    const AMERICAN_EXPRESS            = 'paymentoperator_american_express.png';
    const DANKORT                     = 'paymentoperator_dankort.png';
    const MAESTRO                     = 'paymentoperator_maestro.png';
    const CARTE_BLEUE_NATIONALE       = 'paymentoperator_cartebleue.png';
    const JCB                         = 'paymentoperator_jcb.png';

    /**
     * Retrieves the options array for all paymentoperator
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'file' => self::VISA,
                'label' => $this->_getHelper()->__('Visa'),
                'value' => 'visa',
            ),
            array(
                'file' => self::MASTERCARD,
                'label' => $this->_getHelper()->__('MasterCard'),
                'value' => 'mastercard',
            ),
            array(
                'file' => self::MAESTRO,
                'label' => $this->_getHelper()->__('Maestro'),
                'value' => 'maestro',
            ),
            array(
                'file' => self::AMERICAN_EXPRESS,
                'label' => $this->_getHelper()->__('American Express'),
                'value' => 'american_express',
            ),
            array(
                'file' => self::DANKORT,
                'label' => $this->_getHelper()->__('Dankort'),
                'value' => 'dankort',
            ),
            array(
                'file' => self::CARTE_BLEUE_NATIONALE,
                'label' => $this->_getHelper()->__('Carte Bleue Nationale'),
                'value' => 'carte_bleue_nationale',
            ),
            array(
                'file' => self::JCB,
                'label' => $this->_getHelper()->__('JCB'),
                'value' => 'jcb',
            ),
        );
    }
}