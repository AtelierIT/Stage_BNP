<?php

/**
 * Product:       Xtento_OrderExport (1.2.4)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2012-12-16T13:35:18+01:00
 * File:          app/code/local/Xtento/OrderExport/Model/System/Config/Source/Export/Events.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Model_System_Config_Source_Export_Events
{
    public function toOptionArray($entity = false)
    {
        $optionArray = array();
        $events = Mage::getSingleton('xtento_orderexport/observer_event')->getEvents($entity);
        foreach ($events as $entityEvents) {
            foreach ($entityEvents as $eventId => $eventOptions) {
                $optionArray[$eventId] = $eventOptions['label'];
            }
        }
        return $optionArray;
    }
}