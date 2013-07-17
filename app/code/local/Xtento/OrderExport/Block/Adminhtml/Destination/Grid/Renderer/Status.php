<?php

/**
 * Product:       Xtento_OrderExport (1.2.4)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2013-02-10T17:02:34+01:00
 * File:          app/code/local/Xtento/OrderExport/Block/Adminhtml/Destination/Grid/Renderer/Status.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Block_Adminhtml_Destination_Grid_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        return Mage::helper('xtento_orderexport')->__('Used in <strong>%d</strong> profile(s)', count($row->getProfileUsage()));
    }
}