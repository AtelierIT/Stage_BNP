<?php

class Bonaparte_ImportExport_Block_Adminhtml_Catalogue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('catalogue_grid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('Bonaparte_ImportExport/Catalogue')->getCollection();
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

        $this->addColumn('suffix', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('Suffix'),
            'align'     =>'left',
            'index'     => 'suffix',
        ));

        $this->addColumn('description', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('Start Date'),
            'align'     =>'left',
            'width'     => '120px',
            'type'      => 'datetime',
            'index'     => 'start_date',
        ));

        $this->addColumn('other', array(
            'header'    => Mage::helper('Bonaparte_ImportExport')->__('End Date'),
            'align'     => 'left',
            'width'     => '120px',
            'type'      => 'datetime',
            'index'     => 'end_date',
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}