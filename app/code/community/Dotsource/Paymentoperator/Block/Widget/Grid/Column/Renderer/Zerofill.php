<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Widget_Grid_Column_Renderer_Zerofill
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Format the given value as an paymentoperator transaction code (RefNr).
     *
     * @param Varien_Object $row
     */
    protected function _getValue(Varien_Object $row)
    {
        $value = parent::_getValue($row);

        if (null !== $value) {
            return $this->_getHelper()->getConverter()->formatAsTransactionCode($value);
        }

        return $this->getColumn()->getDefault();
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}