<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_3dsecure_Logos
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Logos
{

    /**
     * Holds a list of all available cc brands that supports 3d secure
     *
     * @var array
     */
    protected $_allowed3DSecureBrands = array(
        self::VERIFIED_BY_VISA          => true,
        self::VISA                      => true,
        self::MASTERCARD                => true,
        self::MASTERCARD_SECURE_CODE    => true,
        self::MAESTRO                   => true,
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
                if (!isset($this->_allowed3DSecureBrands[$value['file']])) {
                    unset($options[$key]);
                }
            }
        }

        return $options;
    }
}