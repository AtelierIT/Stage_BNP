<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Checkposition
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    /** check on payment */
    const PAYMENT       = 'payment';

    /** check on place order */
    const PLACE_ORDER   = 'place_order';


    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::PAYMENT,
                'label' => $this->_getHelper()->__('On list payment')
            ),
            array(
                'value' => self::PLACE_ORDER,
                'label' => $this->_getHelper()->__('On place order')
            )
        );
    }
}