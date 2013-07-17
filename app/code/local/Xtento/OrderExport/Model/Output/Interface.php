<?php

/**
 * Product:       Xtento_OrderExport (1.2.4)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2012-11-25T14:13:08+01:00
 * File:          app/code/local/Xtento/OrderExport/Model/Output/Interface.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

interface Xtento_OrderExport_Model_Output_Interface {
    public function convertData($exportArray);
}