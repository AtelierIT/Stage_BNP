<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/InvoiceController.php';
class Dotsource_Paymentoperator_Adminhtml_MassactionController
    extends Mage_Adminhtml_Sales_Order_InvoiceController
{

    protected function _construct()
    {
        parent::_construct();
        $this->setUsedModuleName('Dotsource_Paymentoperator');
    }

    /**
     * Create for every order a invoice.
     */
    public function massInvoiceAction()
    {
        $this->_processInvoiceMassActions(false);
        $this->_redirect('adminhtml/sales_order/index');
    }


    public function massInvoiceShipmentAction()
    {
        $this->_processInvoiceMassActions(true);
        $this->_redirect('adminhtml/sales_order/index');
    }


    /**
     * Process the invoice creation.
     *
     * @param boolean $createShipment
     */
    protected function _processInvoiceMassActions($createShipment)
    {
        $orderIds       = $this->getRequest()->getPost('order_ids', array());
        $notice         = array();
        $success        = array();
        $error          = array();

        //Filter order ids
        if (!empty($orderIds) && is_array($orderIds)) {
            /* @var $collection Mage_Sales_Model_Mysql4_Order_Collection */
            $collection = Mage::getResourceModel('sales/order_collection');

            //Reset the default cols
            $collection->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns($collection->getResource()->getIdFieldName())
                ->where("{$collection->getResource()->getIdFieldName()} IN (?)", $orderIds)
                ->where(
                    "status IN (?)",
                    array(
                        Dotsource_Paymentoperator_Model_Payment_Abstract::READY_FOR_CAPTURE,
                        Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::READY_FOR_BILLPAY_CAPTURE,
                    )
                );

            //Get the order ids
            $orderIds = $collection->getConnection()->fetchCol($collection->getSelect());
        }

        foreach ($orderIds as $orderId) {
            /* @var $order Mage_Sales_Model_Order */
            $order = null;

            /* @var $invoices Mage_Sales_Model_Mysql4_Order_Invoice_Collection */
            $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                ->addAttributeToSelect('*')
                ->setOrderFilter($orderId);

            //Get the invoice size
            $invoiceSize = $invoices->getSize();

            //Only create invoice if we have no invoices
            if ($invoiceSize > 0) {
                //Get the order for the increment id
                $order = Mage::getModel('sales/order')->load($orderId);

                //Add to the notice
                $notice[] = $this->_getPaymentoperatorHelper()->__(
                    'The order #%s has already %s invoice(s).',
                    $order->getIncrementId(),
                    $invoiceSize
                );
            } else {
                try {
                    //Manipulate the params for the invoice actions
                    Mage::unregister('current_invoice');
                    $this->getRequest()->setParam('order_id', $orderId);

                    //Create the invoice
                    $invoice = $this->_initInvoice(false);

                    //We need a good value here
                    if (!$invoice) {
                        //Get the order for the increment id
                        $order = Mage::getModel('sales/order')->load($orderId);

                        //Create error message for the current order
                        $error[] = $this->_getPaymentoperatorHelper()->__(
                            "Can't create invoice for order #%s.",
                            $order->getIncrementId()
                        );

                        continue;
                    }

                    //Get the order from the invoice
                    $order = $invoice->getOrder();

                    $order->setCustomerNoteNotify(0);
                    $order->setIsInProcess(true);

                    //Check for paymentoperator payment
                    if (!$order->getPayment()->getMethodInstance()
                        instanceof Dotsource_Paymentoperator_Model_Payment_Abstract
                    ) {
                        //Create error message for the current order
                        $error[] = $this->_getPaymentoperatorHelper()->__(
                            "The order #%s was not paid with a paymentoperator payment and can't invoiced.",
                            $order->getIncrementId()
                        );

                        continue;
                    } else if (!$order->getPayment()->canCapture()) {
                        //Create error message for the current order
                        $error[] = $this->_getPaymentoperatorHelper()->__(
                            "The order #%s can't capture.",
                            $order->getIncrementId()
                        );

                        continue;
                    }

                    //Paymentoperator paid orders are always captured online
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    $invoice->register();

                    //Save the invoice
                    $order->addRelatedObject($invoice);

                    //Create the shipment if we need
                    if ($createShipment || (int) $order->getForcedDoShipmentWithInvoice()) {
                        //Create the shipment
                        $shipment = $this->_prepareShipment($invoice);

                        //We need a good value
                        if ($shipment) {
                            $order->addRelatedObject($shipment);
                        } else {
                            //Create error message for the current order
                            $error[] = $this->_getPaymentoperatorHelper()->__(
                                "Can't create shipment for order #%s.",
                                $order->getIncrementId()
                            );
                        }
                    }

                    //Save the order with the related products
                    $order->save();

                    //Get the right message
                    $message    = "";
                    if ($createShipment && $shipment) {
                        $message = "Successfully create invoice and shipment for order #%s in amount of %s.";
                    } else {
                        $message = "Successfully create invoice for order #%s in amount of %s.";
                    }

                    //Add success message
                    $success[] = $this->_getPaymentoperatorHelper()->__(
                        $message,
                        $order->getIncrementId(),
                        $order->getBaseCurrency()->formatPrecision($invoice->getBaseGrandTotal(), 2, array(), false)
                    );
                } catch (Exception $e) {
                    //Get the order for the increment id
                    $order      = Mage::getModel('sales/order')->load($orderId);
                    $message    = "";

                    //Get the right message
                    if ($createShipment) {
                        $message = "Can't create invoice and shipment for order #%s. (%s)";
                    } else {
                        $message = "Can't create invoice for order #%s. (%s)";
                    }

                    //Create error message for the current order
                    $error[] = $this->_getPaymentoperatorHelper()->__(
                        $message,
                        $order->getIncrementId(),
                        $e->getMessage()
                    );
                }
            }
        }

        //No need to show the messages from the
        $this->_getSession()->getMessages(true);

        //There is was nothing to do
        if (empty($notice) && empty($success) && empty($error)) {
            $this->_getSession()->addNotice(
                $this->_getPaymentoperatorHelper()->__(
                    'No orders selected with the status "%s".',
                    Mage::getSingleton('sales/order_config')->getStatusLabel(Dotsource_Paymentoperator_Model_Payment_Abstract::READY_FOR_CAPTURE)
                )
            );
        } else {
            if (!empty($notice)) {
                $this->_getSession()->addNotice(implode('<br/>', $notice));
            }

            if (!empty($success)) {
                $this->_getSession()->addSuccess(implode('<br/>', $success));
            }

            if (!empty($error)) {
                $this->_getSession()->addError(implode('<br/>', $error));
            }
        }
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getPaymentoperatorHelper()
    {
        return Mage::helper('paymentoperator');
    }
}