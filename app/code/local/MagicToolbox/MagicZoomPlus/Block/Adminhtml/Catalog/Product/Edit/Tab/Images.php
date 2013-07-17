<?php

class MagicToolbox_MagicZoomPlus_Block_Adminhtml_Catalog_Product_Edit_Tab_Images extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface {

    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('group_fields_magiczoomplus_images', array('legend' => Mage::helper('magiczoomplus')->__('Images'), 'class' => 'magiczoomplusFieldset'));
        $fieldset->addType('magiczoomplus_gallery', 'MagicToolbox_MagicZoomPlus_Block_Adminhtml_Settings_Edit_Tab_Form_Element_Gallery');
        $fieldset->addField('magiczoomplus_gallery', 'magiczoomplus_gallery', array(
            'label'     => Mage::helper('magiczoomplus')->__('${too.id} gallery'),
            'name'      => 'magiczoomplus[gallery]',
        ));
        $this->setForm($form);
        return parent::_prepareForm();
    }

    public function getTabLabel() {
        return $this->__('MagicZoomPlus Images');
    }

    public function getTabTitle() {
        return $this->__('MagicZoomPlus Images');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    public function getHtmlId() {
        return $this->getId();
    }

    public function getJsObjectName() {
        return $this->getHtmlId().'JsObject';
    }

}

