<?php

class Bonaparte_ImportExport_Block_Adminhtml_Catalogue extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    protected $_addButtonLabel = 'Add New Catalogue';
    protected $_importButtonLabel = 'Import Products';

    public function __construct()
    {
        parent::__construct();
        $this->_controller = 'adminhtml_catalogue';
        $this->_blockGroup = 'Bonaparte_ImportExport';
        $this->_headerText = Mage::helper('Bonaparte_ImportExport')->__('Catalogue');
    }

    protected function _prepareLayout()
    {
        $this->setChild('grid',
            $this->getLayout()->createBlock($this->_blockGroup . '/' . $this->_controller . '_grid',
                $this->_controller . '.grid')->setSaveParametersInSession(true));
        return parent::_prepareLayout();
    }
}