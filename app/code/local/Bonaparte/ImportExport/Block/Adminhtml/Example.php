<?php

class Bonaparte_ImportExport_Block_Adminhtml_Example extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_addButtonLabel = 'Add New Example';

    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_example';
        $this->_blockGroup = 'Bonaparte_ImportExport';
        $this->_headerText = Mage::helper('Bonaparte_ImportExport')->__('Examples');
    }

    protected function _prepareLayout()
    {
        $this->setChild('grid',
            $this->getLayout()->createBlock($this->_blockGroup . '/' . $this->_controller . '_grid',
                $this->_controller . '.grid')->setSaveParametersInSession(true));
        return parent::_prepareLayout();
    }
}