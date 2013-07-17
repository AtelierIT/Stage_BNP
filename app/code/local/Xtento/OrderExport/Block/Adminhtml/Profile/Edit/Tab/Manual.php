<?php

/**
 * Product:       Xtento_OrderExport (1.2.4)
 * ID:            Local Deploy
 * Packaged:      2013-06-27T16:18:21+02:00
 * Last Modified: 2013-04-28T18:11:54+02:00
 * File:          app/code/local/Xtento/OrderExport/Block/Adminhtml/Profile/Edit/Tab/Manual.php
 * Copyright:     Copyright (c) 2013 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

class Xtento_OrderExport_Block_Adminhtml_Profile_Edit_Tab_Manual extends Xtento_OrderExport_Block_Adminhtml_Widget_Tab
{
    protected function _prepareForm()
    {
        $model = Mage::registry('profile');

        $form = new Varien_Data_Form();
        $this->setForm($form);

        $fieldset = $form->addFieldset('manual_fieldset', array(
            'legend' => Mage::helper('xtento_orderexport')->__('Manual Export Settings'),
            'class' => 'fieldset-wide',
        ));

        $fieldset->addField('save_files_manual_export', 'select', array(
            'label' => Mage::helper('xtento_orderexport')->__('Save files on destinations for manual exports'),
            'name' => 'save_files_manual_export',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note' => Mage::helper('xtento_orderexport')->__('Do you want to save exported files on the configured export destinations when exporting manually? Or do you just want them to be saved on the configured export destinations for automatic exports?')
        ));

        $fieldset->addField('start_download_manual_export', 'select', array(
            'label' => Mage::helper('xtento_orderexport')->__('Serve files to browser after exporting manually'),
            'name' => 'start_download_manual_export',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'note' => Mage::helper('xtento_orderexport')->__('When exporting manually from the grid or "Manual Export" screen, if set to "Yes", the exported file will be served to the browser automatically after exporting. Ultimately this is controlled whether you check the "Serve file to browser after exporting" checkbox on the manual export screen or not.')
        ));

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}