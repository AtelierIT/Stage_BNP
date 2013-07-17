<?php

class Bonaparte_ImportExport_Block_Adminhtml_Example_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('example_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('Bonaparte_ImportExport/Example')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'id',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('Name'),
            'align'     =>'left',
            'index'     => 'name',
        ));

        $this->addColumn('description', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('Description'),
            'align'     =>'left',
            'index'     => 'description',
        ));

        $this->addColumn('other', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('Other'),
            'align'     => 'left',
            'index'     => 'other',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}