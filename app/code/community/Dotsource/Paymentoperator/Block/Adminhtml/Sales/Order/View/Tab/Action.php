<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 05.05.2010 14:44:15
 *
 * Contributors:
 * dcarl - initial contents
 */

/**
 * Order Shipments grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Dotsource_Paymentoperator_Block_Adminhtml_Sales_Order_View_Tab_Action
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('paymentoperator_action');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        $this->setUseAjax(true);
    }


    /**
     * Prepares the collection to be sholwn in the paymentoperator action grid.
     *
     * @return  Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        /* @var $collection Dotsource_Paymentoperator_Model_Mysql4_Action_Collection */
        $collection = Mage::getResourceModel('paymentoperator/action_collection');
        $collection
            ->addFieldToFilter(
                'transaction_id',
                $this->_getPaymentoperatorTransactionId()
            );

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


    /**
     * Override for format the transaction number.
     *
     * @return unknown
     */
    protected function _afterLoadCollection()
    {
        //Format the as transaction_id as transaction_code
        foreach ($this->getCollection() as $item) {
            $item->setData(
                'transaction_code',
                sprintf("%09s", $this->_getHelper()->getConverter()->formatAsTransactionCode(
                    $item->getData('transaction_id')
                ))
            );
        }

        //Process parent action
        parent::_afterLoadCollection();

        return $this;
    }


    /**
     * Prepares the backend grid that shows the paymentoperator transactions
     * of the current order.
     *
     * @return  Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'transaction_code',
            array(
                'header' => Mage::helper('paymentoperator')->__('RefNr'),
                'index'  => 'transaction_code',
            )
        );

        $this->addColumn(
            'request_payid',
            array(
                'header' => Mage::helper('paymentoperator')->__('Request PayID'),
                'index' => 'request_payid',
            )
        );

        $this->addColumn(
            'response_payid',
            array(
                'header' => Mage::helper('paymentoperator')->__('Response PayID'),
                'index' => 'response_payid',
            )
        );

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('paymentoperator')->__('Action'),
                'index' => 'action',
            )
        );

        $this->addColumn(
            'created_at',
            array(
                'header' => Mage::helper('paymentoperator')->__('Created At'),
                'index'  => 'created_at',
                'type'   => 'datetime'
            )
        );

        $this->addColumn(
            'error_description',
            array(
                'header' => Mage::helper('paymentoperator')->__('Description'),
                'index'  => 'error_description',
                'frame_callback' => array($this, 'decorateErrorDescription')
            )
        );

        $this->addColumn(
            'error_code',
            array(
                'header' => Mage::helper('paymentoperator')->__('Error Code'),
                'index'  => 'error_code',
                'width'  => '50px',
            )
        );

        $this->addColumn(
            'error',
            array(
                'header'  => Mage::helper('paymentoperator')->__('Successful'),
                'index'   => 'error',
                'type'    => 'options',
                'options' => array(
                    1 => $this->__('No'),
                    0 => $this->__('Yes')
                ),
                'frame_callback' => array($this, 'decorateError')
            )
        );

        return parent::_prepareColumns();
    }


    /**
     * Decorate error column values
     *
     * @param   string                                  $value
     * @param   Dotsource_Paymentoperator_Model_Action         $row
     * @param   Mage_Adminhtml_Block_Widget_GridColumn  $column
     * @param   boolean                                 $isExport
     * @return  string
     */
    public function decorateError($value, $row, $column, $isExport)
    {
        if (isset($this->_invalidatedTypes[$row->getId()])) {
            $cell = '<span class="grid-severity-minor"><span>'.$this->__('Invalidated').'</span></span>';
        } else {
            if ($row->getError()) {
                $cell = '<span class="grid-severity-critical"><span>'.$value.'</span></span>';
            } else {
                $cell = '<span class="grid-severity-notice"><span>'.$value.'</span></span>';
            }
        }
        return $cell;
    }


    /**
     * Truncates the content of the error_description column.
     *
     * @param   string                                  $value
     * @param   Dotsource_Paymentoperator_Model_Action         $row
     * @param   Mage_Adminhtml_Block_Widget_GridColumn  $column
     * @param   boolean                                 $isExport
     * @return  string
     */
    public function decorateErrorDescription($value, $row, $column, $isExport)
    {
        $noNeed = '';
        return Mage::helper('core/string')->truncate($value, 255, '...', $noNeed, false);
    }


    /**
     * Retreives teh grid url that is used for resorting and searching
     * in the action grid.
     *
     * @return  string
     */
    public function getGridUrl()
    {
        return $this->getUrl('paymentoperator/adminhtml_action/grid', array('_current' => true));
    }


    /**
     * Retrieves the label for the tab in the sales view.
     *
     * @return  string
     */
    public function getTabLabel()
    {
        return Mage::helper('paymentoperator')->__('PaymentOperator Transaction');
    }


    /**
     * Retrieves the title for the tab in the sales view.
     *
     * @return  string
     */
    public function getTabTitle()
    {
        return Mage::helper('paymentoperator')->__('PaymentOperator Transaction');
    }


    /**
     * Indicates if it's possilbe to show the tab.
     *
     * @return  boolean
     */
    public function canShowTab()
    {
        return true;
    }


    /**
     * Indicates if the tab should be hidden.
     *
     * @return  boolean
     */
    public function isHidden()
    {
        return false;
    }


    /**
     * Retrieves the transaction id of the paymentoperator transaction.
     *
     * @return  integer
     */
    protected function _getPaymentoperatorTransactionId()
    {
        return (int)Mage::registry('current_order')->getPayment()->getPaymentoperatorTransactionId();
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