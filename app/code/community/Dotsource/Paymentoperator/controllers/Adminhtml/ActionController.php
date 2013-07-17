<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 05.05.2010 15:37:47
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Adminhtml_ActionController
    extends Mage_Adminhtml_Controller_Action
{

    /**
     * Initialize order model instance.
     * @see Mage_Adminhtml_Sales_OrderController::_initOrder();
     *
     * @return Mage_Sales_Model_Order || false
     */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }

    /**
     * This action causes the generation of the action grid in the sales view.
     *
     * @return  null
     */
    public function gridAction()
    {
        $this->_initOrder();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('paymentoperator/adminhtml_sales_order_view_tab_action')->toHtml()
        );
    }
}