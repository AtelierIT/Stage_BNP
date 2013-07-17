<?php

/**
 * Product:       Xtento_XtCore (1.0.0)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2012-10-23T17:35:17+02:00
 * File:          app/code/local/Xtento/XtCore/Helper/Filesystem.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_XtCore_Helper_Filesystem extends Mage_Core_Helper_Abstract
{
    // Get module dir without trailing slash
    public function getModuleDir($module)
    {
        return Mage::getConfig()->getOptions()->getCodeDir() . DS . 'local' . DS . substr(str_replace('_', DS, get_class($module)), 0, strpos(get_class($module), '_', strpos(get_class($module), '_') + strlen('_'))) . DS . 'etc';
    }
}