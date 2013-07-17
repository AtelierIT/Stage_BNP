<?php

/**
 * Product:       Xtento_XtCore (1.0.0)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2013-06-09T19:16:27+02:00
 * File:          app/code/local/Xtento/XtCore/Model/Observer/Cron.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_XtCore_Model_Observer_Cron
{
    public function run()
    {
        return true;
    }

    public function getLastExecution()
    {
        return Mage::getStoreConfig('xtcore/crontest/last_execution');
    }

    public function getTimestamp()
    {
        return (string)time();
    }

    public function checkCronjob()
    {
        $lastExecution = $this->getLastExecution();
        if (empty($lastExecution)) {
            return false;
        }
        $differenceInSeconds = $this->getTimestamp() - $lastExecution;
        // If the cronjob has been executed within the last 15 minutes, return true
        return $differenceInSeconds < (60 * 15);
    }
}
