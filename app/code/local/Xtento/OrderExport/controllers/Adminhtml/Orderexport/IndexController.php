<?php

/**
 * Product:       Xtento_OrderExport (1.2.4)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2013-01-09T16:19:23+01:00
 * File:          app/code/local/Xtento/OrderExport/controllers/Adminhtml/Orderexport/IndexController.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Adminhtml_OrderExport_IndexController extends Xtento_OrderExport_Controller_Abstract
{
    public function redirectAction()
    {
        $redirectController = Mage::getStoreConfig('orderexport/general/default_page');
        if (!$redirectController) {
            $redirectController = 'orderexport_manual';
        }
        $this->_redirect('*/'.$redirectController);
    }

    public function installationAction() {
        Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('xtento_orderexport')->__('The extension has not been installed properly. The required database tables have not been created yet. Please check out our <a href="http://support.xtento.com/wiki/Troubleshooting:_Database_tables_have_not_been_initialized" target="_blank">wiki</a> for instructions. After following these instructions access the module at Sales > Sales Export again and do not try to refresh this page.'));
        $this->loadLayout();
        $this->renderLayout();
    }

    public function disabledAction() {
        Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('xtento_orderexport')->__('The extension is currently disabled. Please go to System > XTENTO Extensions > Sales Export Configuration to enable it. After disabling access the module at Sales > Sales Export again and do not try to refresh this page.'));
        $this->loadLayout();
        $this->renderLayout();
    }
}