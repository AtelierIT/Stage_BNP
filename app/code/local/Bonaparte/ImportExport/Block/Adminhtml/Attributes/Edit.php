<?php
class Bonaparte_ImportExport_Block_Adminhtml_Attributes_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct(){
        parent::__construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'attributes';
        $this->_controller = 'adminhtml_attributes';
        $this->_mode = 'edit';

        $this->_updateButton('save', 'label', Mage::helper('attributes')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('attributes')->__('Delete Item'));
    }

    public function getHeaderText(){
        if(Mage::registry('attributes_data') && Mage::registry('attributes_data')->getId())
            return Mage::helper('attributes')->__("Edit Item '%s'", $this->escapeHtml(Mage::registry('attributes_data')->getTitle()));
        return Mage::helper('attributes')->__('Add Item');
    }
}