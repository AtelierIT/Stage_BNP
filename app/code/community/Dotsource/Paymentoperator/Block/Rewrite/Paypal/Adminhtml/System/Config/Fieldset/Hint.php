<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Rewrite_Paypal_Adminhtml_System_Config_Fieldset_Hint
    extends Mage_Paypal_Block_Adminhtml_System_Config_Fieldset_Hint
{

    /**
     * CR: Remove the PayPal backend hint.
     *
     * @param $element
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return '';
    }
}
