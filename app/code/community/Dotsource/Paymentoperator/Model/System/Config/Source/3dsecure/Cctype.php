<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_3dsecure_Cctype
    extends Mage_Adminhtml_Model_System_Config_Source_Payment_Cctype
{

    /**
     * Holds a list of all available cc brands that supports 3d secure
     *
     * @var array
     */
    protected $_allowed3DSecureBrands = array(
        'VI'    => true,
        'MC'    => true,
        'SM'    => true,
    );


    /**
     * Return a list of cc brands that supports 3d secure.
     *
     * @return array
     */
    public function toOptionArray()
    {
        //Get all cc brands
        $options = parent::toOptionArray();

        if ($options && is_array($options)) {
            foreach ($options as $key => $value) {
                if (!isset($this->_allowed3DSecureBrands[strtoupper($value['value'])])) {
                    unset($options[$key]);
                }
            }
        }

        return $options;
    }
}