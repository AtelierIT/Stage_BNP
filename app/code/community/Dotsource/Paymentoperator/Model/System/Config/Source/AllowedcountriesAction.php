<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_AllowedcountriesAction
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    const ALLOW_ALL_COUNTRIES 		= 1;

    const ALLOW_ANY_COUNTRIES   	= 2;

    const RESTRICT_ANY_COUNTRIES	= 3;

    public function toOptionArray()
    {
        return array(
            array(	//(alles erlauben)
                'value' => self::ALLOW_ALL_COUNTRIES,
                'label' => $this->_getHelper()->__('Allow all countries')
            ),
            array(	//(Laender zulassen)
                'value' => self::ALLOW_ANY_COUNTRIES,
                'label' => $this->_getHelper()->__('Allow any countries')
            ),
            array(	//(Laender verbieten)
                'value' => self::RESTRICT_ANY_COUNTRIES,
                'label' => $this->_getHelper()->__('Restrict any countries')
            )
        );
    }
}