<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Giropay_Oftmethod
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    /**
     * Return the configured otfMethod for giropay.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Dotsource_Paymentoperator_Model_Payment_Giropay::OTF_METHOD_GIROPAY,
                'label' => $this->_getHelper()->__('giropay')
            ),
            array(
                'value' => Dotsource_Paymentoperator_Model_Payment_Giropay::OTF_METHOD_PAGO,
                'label' => $this->_getHelper()->__('Pago')
            )
        );
    }
}