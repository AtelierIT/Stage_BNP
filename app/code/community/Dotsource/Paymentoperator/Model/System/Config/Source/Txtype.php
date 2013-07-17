<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 28.04.2010 11:00:58
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Model_System_Config_Source_Txtype
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    const ORDER = 'Order';
    const AUTH  = 'Auth';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::ORDER,
                'label' => $this->_getHelper()->__('Order')
            ),
            array(
                'value' => self::AUTH,
                'label' => $this->_getHelper()->__('Authorization')
            )
        );
    }
}