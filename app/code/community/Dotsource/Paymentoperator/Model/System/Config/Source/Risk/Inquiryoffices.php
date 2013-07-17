<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Risk_Inquiryoffices
    extends Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    /** Holds all inquiry offices */
    public static $inquiryoffices = array(
        1 => 'Schufa',
        2 => 'Universum',
        3 => 'arvato Infoscore',
        4 => 'Bürgel',
        5 => 'Creditreform',
    );


    /**
     * Return the inquiry offices options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = array();

        foreach (self::$inquiryoffices as $key => $name) {
            $data[] = array(
                'value' => $key,
                'label' => $name
            );
        }

        return $data;
    }
}