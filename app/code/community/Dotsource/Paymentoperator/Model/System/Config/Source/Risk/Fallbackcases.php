<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Fallbackcases
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    /** Const for undefined response */
    const UNDEFINED_RESPONSE    = 'undefined_response';

    /** Const for outside of germany */
    const OUTSIDE_OF_GERMANY    = 'outside_of_germany';

    /** Missing address check */
    const MISSING_ADDRESS_CHECK = 'missing_address_check';


    /** Holds the fallback cases */
    public static $fallbackCases = array(
        self::OUTSIDE_OF_GERMANY    => 'Billing address outside of Germany',
        self::MISSING_ADDRESS_CHECK => 'Missing valid paymentoperator address check',
        self::UNDEFINED_RESPONSE    => 'Undefined response from paymentoperator',
    );


    /**
     * Return the inquiry offices options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = array();

        foreach (self::$fallbackCases as $key => $name) {
            $data[] = array(
                'value' => $key,
                'label' => $this->_getHelper()->__($name)
            );
        }

        return $data;
    }
}