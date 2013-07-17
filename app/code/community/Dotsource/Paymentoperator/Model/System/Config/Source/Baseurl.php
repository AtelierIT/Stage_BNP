<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Baseurl
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{
    /** Const for default */
    const NETKAUF   = 'https://www.netkauf.de/paygate/';

    /** const for alternative */
    const PAYMENTOPERATOR  = 'https://www.computop-paygate.com/';

    public function toOptionArray()
    {
        return array(
            array(	//(default)
                'value' => self::NETKAUF,
                'label' => $this->_getHelper()->__(self::NETKAUF)
            ),
//            array(	//(altenativ)
//                'value' => self::PAYMENTOPERATOR,
//                'label' => $this->_getHelper()->__(self::PAYMENTOPERATOR)
//            )
        );
    }
}