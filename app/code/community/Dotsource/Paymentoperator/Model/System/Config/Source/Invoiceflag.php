<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 01.02.2011 03:18:28
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Invoiceflag
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{
    const FLAG_INVOICE_NO       = 0;
    const FLAG_INVOICE_TEST     = 2;
    const FLAG_INVOICE_POST     = 4;
    const FLAG_INVOICE_EMAIL    = 8;
    const FLAG_INVOICE_PARTIAL  = 16;
    const FLAG_INVOICE_TELEPHON = 512;
    const FLAG_INVOICE_PIN      = 1024;

    /**
     * Retrieves the options array for all known flags.
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::FLAG_INVOICE_NO,
                'label' => $this->_getHelper()->__('No invoice')
            ),
            array(
                'value' => self::FLAG_INVOICE_TEST,
                'label' => $this->_getHelper()->__('Test invoice')
            ),
            array(
                'value' => self::FLAG_INVOICE_POST,
                'label' => $this->_getHelper()->__('Post invoice')
            ),
            array(
                'value' => self::FLAG_INVOICE_EMAIL,
                'label' => $this->_getHelper()->__('E-Mail invoice')
            ),
            array(
                'value' => self::FLAG_INVOICE_PARTIAL,
                'label' => $this->_getHelper()->__('Partial invoice')
            ),
            array(
                'value' => self::FLAG_INVOICE_TELEPHON,
                'label' => $this->_getHelper()->__('Telephon invoice')
            ),
            array(
                'value' => self::FLAG_INVOICE_PIN,
                'label' => $this->_getHelper()->__('PIN code invoice')
            )
        );
    }
}

