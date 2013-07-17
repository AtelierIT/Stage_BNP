<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    /** Const for auth mode */
    const AUTHORIZE 	= Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE;

    /** const for booking mode */
    const BOOKING   	= Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE;

    /** const for booking mode */
    const TIMEDBOOKING	= 'timebooking_authorize_capture';

    public function toOptionArray()
    {
        return array(
            array(	//(manuell)
                'value' => self::AUTHORIZE,
                'label' => $this->_getHelper()->__('Debit on Delivery')
            ),
            array(	//(auto)
                'value' => self::BOOKING,
                'label' => $this->_getHelper()->__('Immediate payment')
            ),
            array(	//("Zahl")
                'value' => self::TIMEDBOOKING,
                'label' => $this->_getHelper()->__('Debit in x hours')
            )
        );
    }
}