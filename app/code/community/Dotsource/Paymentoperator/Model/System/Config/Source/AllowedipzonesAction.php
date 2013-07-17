<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_AllowedipzonesAction
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    const ALLOW_ALL_ZONES 		= 1;

    const ALLOW_ANY_ZONES   	= 2;

    const RESTRICT_ANY_ZONES    = 3;

    public function toOptionArray()
    {
        return array(
            array(	//(alle IP Zonen erlauben)
                'value' => self::ALLOW_ALL_ZONES,
                'label' => $this->_getHelper()->__('Allow all ip zones')
            ),
            array(	//(IP Zonen zulassen)
                'value' => self::ALLOW_ANY_ZONES,
                'label' => $this->_getHelper()->__('Allow specific ip zones')
            ),
            array(	//(IP Zonen verbieten)
                'value' => self::RESTRICT_ANY_ZONES,
                'label' => $this->_getHelper()->__('Restrict specific ip zones')
            )
        );
    }
}