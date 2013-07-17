<?php

class Bonaparte_ImportExport_Block_Adminhtml_Attributes extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct(){
        parent::__construct();
        $this->_controller = 'adminhtml_attributes';
        $this->_blockGroup = 'Bonaparte_ImportExport';
        $this->_headerText = Mage::helper('Bonaparte_ImportExport')->__('CVL list of Attributes');
        $this->_addButtonLabel = Mage::helper('Bonaparte_ImportExport')->__('Add Selected Attributes');

    }
    protected function _prepareLayout()
    {
        $this->setChild('grid',
            $this->getLayout()->createBlock($this->_blockGroup . '/' . $this->_controller . '_grid',
                $this->_controller . '.grid')->setSaveParametersInSession(true));
        return parent::_prepareLayout();
    }
}