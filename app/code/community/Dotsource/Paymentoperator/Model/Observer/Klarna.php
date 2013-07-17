<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Observer_Klarna
{

    /**
     * Return true if the payment method is klarna.
     *
     * @param Mage_Payment_Model_Method_Abstract $paymentMethod
     * @return boolean
     */
    protected function _isKlarnaPayment(Mage_Payment_Model_Method_Abstract $paymentMethod)
    {
        return $paymentMethod
            && $paymentMethod instanceof Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract;
    }


    /**
     * Invoice can not create manual so we show a error if some one try to
     * create one.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onInvoiceCreate(Varien_Event_Observer $observer)
    {
        /* @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoice    = $observer->getInvoice();
        $order      = $invoice->getOrder();

        //Only for klarna payment methods
        if (!$this->_isKlarnaPayment($order->getPayment()->getMethodInstance())) {
            return;
        }

        //Check if the invoice was created automatically
        if (!$invoice->getPaymentoperatorKlarnaAutomaticallyCreated()) {
            Mage::throwException(
                $this->_getHelper()->__(
                    "It's not allowed to create a invoice manually for order who was paid with klarna. " .
                    "If you ship the order a invoice will create automatically."
                )
            );
        }
    }


    /**
     * For klarna payments create the invoice automatically on shipment create.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onShipmentCreate(Varien_Event_Observer $observer)
    {
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        /* @var $order Mage_Sales_Model_Order */
        $shipment   = $observer->getShipment();
        $order      = $shipment->getOrder();

        //Only use on klarna payment and we are in the shipping create progress
        if (!$this->_isKlarnaPayment($order->getPayment()->getMethodInstance())) {
            return;
        }

        //Check create order ability
        if (!$order->canInvoice()) {
            Mage::throwException($this->_getHelper()->__('The order does not allow creating an invoice.'));
        }

        //Check for full shipment
        //#1 create the map key is product id value is the invoiceable qty
        $orderedProductsMap = array();
        foreach ($order->getAllVisibleItems() as $orderItem) {
            /* @var $orderItem Mage_Sales_Model_Order_Item */
            $orderedProductsMap[$orderItem->getId()] = $orderItem->getQtyToInvoice();
        }

        //#2 Check for the full qty
        foreach ($shipment->getAllItems() as $shipmentItem) {
            /* @var $shipmentItem Mage_Sales_Model_Order_Shipment_Item */
            //Data we need
            $orderItemId = $shipmentItem->getOrderItemId();
            $shipmentQty = $shipmentItem->getQty();

            //Check for
            if (!isset($orderedProductsMap[$orderItemId]) || $orderedProductsMap[$orderItemId] != $shipmentQty) {
                break;
            }

            //Unset for success
            unset($orderedProductsMap[$orderItemId]);
        }

        //If the array is not empty this means the qty is wrong or the order item was not in the shipment
        if ($orderedProductsMap) {
            Mage::throwException($this->_getHelper()->__("It's only possible to create a full shipment."));
        }

        //Create the invoice
        $invoice = $this->_initInvoice($order);

        //Check for a valid invoice
        if (!$invoice) {
            Mage::throwException($this->_getHelper()->__('Cannot create an invoice without products.'));
        }

        //Check if we have products in the invoice
        if (!$invoice->getTotalQty()) {
            Mage::throwException($this->_getHelper()->__('Cannot create an invoice without products.'));
        }

        //Set the capture case to online and automatically
        $invoice
            ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
            ->setPaymentoperatorKlarnaAutomaticallyCreated(true);

        //Register the invoice
        $invoice->register();

        //Don't send a email
        $invoice->setEmailSent(false);

        //Save the invoice
        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->save();
    }


    /**
     * Create a invoice with the qty from the shipping create request.
     *
     * @param $order
     * @return Mage_Sales_Model_Order_Invoice
     */
    protected function _initInvoice(Mage_Sales_Model_Order $order)
    {
        //Try to create a service model
        /* @var $service Mage_Sales_Model_Service_Order */
        $service    = Mage::getModel('sales/service_order', $order);
        $qtys       = $this->_getShippingQty();

        //Check for service model
        if ($service && method_exists($service, 'prepareInvoice')) {
            return $service->prepareInvoice($qtys);
        }

        //Use the old way to create the invoice (Magento 1.4.0.X)
        /* @var $orderItem Mage_Sales_Model_Order_Item */
        /* @var $convertor Mage_Sales_Model_Convert_Order */
        $convertor      = Mage::getModel('sales/convert_order');
        $invoice        = $convertor->toInvoice($order);
        $itemsToInvoice = 0;

        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->isDummy()
                && !$orderItem->getQtyToInvoice()
                && $orderItem->getLockedDoInvoice()
            ) {
                continue;
            }

            if ($order->getForcedDoShipmentWithInvoice()
                && $orderItem->getLockedDoShip()
            ) {
                continue;
            }

            if ($orderItem->isDummy()
                && !empty($qtys)
                && !$this->_needToAddDummy($orderItem, $qtys)
            ) {
                continue;
            }

            //Convert
            $item = $convertor->itemToInvoiceItem($orderItem);

            if (isset($qtys[$orderItem->getId()])) {
                $qty = $qtys[$orderItem->getId()];
            } else {
                if ($orderItem->isDummy()) {
                    $qty = 1;
                } else {
                    $qty = $orderItem->getQtyToInvoice();
                }
            }

            $itemsToInvoice += floatval($qty);
            $item->setQty($qty);
            $invoice->addItem($item);
        }

        //Items available?
        if ($itemsToInvoice <= 0) {
            Mage::throwException($this->__('Invoice without products could not be created.'));
        }

        $invoice->collectTotals();

        return $invoice;
    }


    /**
     * Decides if we need to create dummy invoice item or not
     * for eaxample we don't need create dummy parent if all
     * children are not in process
     *
     * @param Mage_Sales_Model_Order_Item $item
     * @param array $qtys
     * @return bool
     */
    protected function _needToAddDummy($item, $qtys)
    {
        if ($item->getHasChildren()) {
            foreach ($item->getChildrenItems() as $child) {
                if (isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) {
                    return true;
                }
            }
            return false;
        } elseif ($item->getParentItem()) {
            if (isset($qtys[$item->getParentItem()->getId()]) && $qtys[$item->getParentItem()->getId()] > 0) {
                return true;
            }
            return false;
        }
    }


    /**
     * Return the shipping qty from the request.
     *
     * @return array
     */
    protected function _getShippingQty()
    {
        $data = Mage::app()->getRequest()->getParam('shipment');

        if (isset($data['items'])) {
            $qtys = $data['items'];
        } else {
            $qtys = array();
        }

        return $qtys;
    }


    /**
     * Return the module helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}