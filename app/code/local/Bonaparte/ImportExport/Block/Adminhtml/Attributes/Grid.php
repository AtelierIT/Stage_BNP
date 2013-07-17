<?php

class Bonaparte_ImportExport_Block_Adminhtml_Attributes_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('attributesGrid');
        $this->setDefaultSort('attribute_code');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('Bonaparte_ImportExport/Attributes')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('attribute_check', array(
            'header'  => Mage::helper('attributes')->__('Check'),
            'align'  => 'right',
            'width'  => '200px',
            'index'  => 'attribute_check',
        ));

        $this->addColumn('attribute_code', array(
            'header'  => Mage::helper('attributes')->__('Attribute Code'),
            'align'  => 'right',
            'width'  => '200px',
            'index'  => 'attribute_code',
        ));

        $this->addColumn('attribute_label', array(
            'header'  => Mage::helper('attributes')->__('Attribute Label'),
            'align'  => 'left',
            'width'  => '200px',
            'index'  => 'attribute_label',
        ));

        $this->addColumn('no_opt_values', array(
            'header'  => Mage::helper('attributes')->__('Number of Optional Values'),
            'align'  => 'right',
            'width'  => '200px',
            'index'  => 'no_opt_values',
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction(){
        $this->setMassactionIdField('attribute_code');
        $this->getMassactionBlock()->setFormFieldName('attributes');

        $this->getMassactionBlock()->addItem('add', array(
            'label'     => Mage::helper('attributes')->__('Add'),
            'url'       => $this->getUrl('*/*/massAdd'),
            'confirm'   => Mage::helper('attributes')->__('Are you sure?')
        ));
        return $this;
    }

    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}