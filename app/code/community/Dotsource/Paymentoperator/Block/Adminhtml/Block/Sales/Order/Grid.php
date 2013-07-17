<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Adminhtml_Block_Sales_Order_Grid
    extends Mage_Adminhtml_Block_Sales_Order_Grid
{

    /**
     * Extends the order grid cols.
     */
    protected function _prepareColumns()
    {
        //Only need for magento version < 1.4.1.0
        if ($this->_getHelper()->getFeatureHelper()->hasGridTables()) {
            return parent::_prepareColumns();
        }

        //Add the paymentoperator RefNr
        $this->addColumnAfter('paymentoperator_transaction_id',
            array(
                'header'    => 'PO RefNr',
                'title'     => 'PaymentOperator Reference Number',
                'index'     => 'paymentoperator_transaction_id',
                'type'      => 'text',
                'width'     => '65px',
                'renderer'  => 'paymentoperator/widget_grid_column_renderer_zerofill',
                'filter_condition_callback' => array($this, '_filterPaymentoperatorTransactionId')
            ),
            'real_order_id'
        );

        //Add the magentodefault cols
        return parent::_prepareColumns();
    }


    /**
     * Extends the collection with the paymentoperator_transaction_id attribute from the
     * order payment entity.
     */
    protected function _prepareCollection()
    {
        //Only need for magento version < 1.4.1.0
        if ($this->_getHelper()->getFeatureHelper()->hasGridTables()) {
            return parent::_prepareCollection();
        }

        //Default magento logic
        $collection = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToSelect('*')
            ->joinAttribute('billing_firstname', 'order_address/firstname', 'billing_address_id', null, 'left')
            ->joinAttribute('billing_lastname', 'order_address/lastname', 'billing_address_id', null, 'left')
            ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
            ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
            ->addExpressionAttributeToSelect('billing_name',
                'CONCAT({{billing_firstname}}, " ", {{billing_lastname}})',
                array('billing_firstname', 'billing_lastname'))
            ->addExpressionAttributeToSelect('shipping_name',
                'CONCAT({{shipping_firstname}},  IFNULL(CONCAT(\' \', {{shipping_lastname}}), \'\'))',
                array('shipping_firstname', 'shipping_lastname'));


        //Join the EAV attribute
        $attributeModel = Mage::getModel('eav/config');
        $refNrAttribute = $attributeModel->getAttribute('order_payment', 'paymentoperator_transaction_id');

        $collection->getSelect()
            ->joinLeft( //Join the payment entity id
                array('payment' => $collection->getTable('sales/order_entity')),
                'e.entity_id=payment.parent_id AND payment.entity_type_id=' . $refNrAttribute->getEntityTypeId(),
                null
            )
            ->joinLeft( //Join the paymentoperator transaction id
                array('po_refnr' => $refNrAttribute->getBackendTable()),
                'payment.entity_id=po_refnr.entity_id AND po_refnr.attribute_id=' . $refNrAttribute->getId(),
                array('paymentoperator_transaction_id' => 'po_refnr.value')
            );

        //Add the sort order
        switch($this->getParam($this->getVarNameSort())) {
            case 'paymentoperator_transaction_id':
                $sortOrder = trim(strtoupper($this->getParam($this->getVarNameDir())));

                if (Zend_Db_Select::SQL_DESC == $sortOrder) {
                    $sortOrder = Zend_Db_Select::SQL_DESC;
                } else {
                    $sortOrder = Zend_Db_Select::SQL_ASC;
                }

                $collection->getSelect()->order("po_refnr.value $sortOrder");
                break;
        }

        $this->setCollection($collection);
        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }


    /**
     * Filter callback for paymentoperator_transaction_id.
     *
     * @param unknown_type $collection
     * @param unknown_type $column
     */
    protected function _filterPaymentoperatorTransactionId($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $collection->getSelect()->where('po_refnr.value = ?', $value);
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}