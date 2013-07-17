<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.01.2011 13:33:02
 *
 * Contributors:
 * mdaehnert - initial contents
 */

class Dotsource_Paymentoperator_Block_Adminhtml_System_Config_Form_Field_Addresscheck
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Custom template
     *
     * @var string
     */
    protected $_template = 'paymentoperator/system/config/form/field/addresscheck.phtml';

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = parent::render($element);

        return $html . $this->toHtml();
    }

}
