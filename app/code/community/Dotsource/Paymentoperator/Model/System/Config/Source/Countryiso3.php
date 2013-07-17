<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_System_Config_Source_Countryiso3
{

    /**
	 * Retrieves the options array for all known countries.
	 *
	 * @return  array
	 */
    public function toOptionArray()
    {
        $collection = Mage::getResourceModel('directory/country_collection')
            ->loadData();

        $sort = array();
        foreach ($collection as $countryModel) {
            $name = Mage::app()->getLocale()->getCountryTranslation($countryModel->getCountryId());
            if ($name) {
                $sort[$name] = $countryModel->getIso3Code();
            }
        }

        ksort($sort, SORT_STRING);

        $options = array();
        foreach ($sort as $label => $value) {
            $options[] = array(
               'value' => $value,
               'label' => $label
            );
        }

        return $options;
    }
}