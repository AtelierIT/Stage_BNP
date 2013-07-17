<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Observer
{

    /**
     * Delete all transaction information from the paymentoperator session, quote
     * and quote payment to create a new transaction model.
     */
    public function deletePaymentoperatorTransaction()
    {
        //Clear the session
        Mage::getSingleton('paymentoperator/session')->clearSession();

        //Get the quote for delete the transaction id from the payment
        //and delete the reserved increment id
        $quote = $this->_getHelper()->getFrontendQuote();

        //Have a valid quote?
        if (!$quote || !$quote->getId() || !$quote->getPayment()->getPaymentoperatorTransactionId()) {
            return;
        }

        //Reset the paymentoperator transaction id and the save the quote
        $quote->getPayment()
            ->setPaymentoperatorTransactionId(null)
            ->save();
    }

    /**
     * Clear the payment deactivation for the current.
     */
    public function clearPaymentDeactivation()
    {
        $this->_getHelper()->getPaymentHelper()->clearPaymentDeactivation();
    }

    /**
     * Add mass actions to the sales order view grid.
     *
     * @param unknown_type $observer
     */
    public function onAdminhtmlBlockHtmlBefore($observer)
    {
        /* @var $block Mage_Adminhtml_Block_Sales_Order_Grid */
        $block = $observer->getBlock();

        //Only need to add mass actions for Mage_Adminhtml_Block_Sales_Order_Grid block
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            //Create a full invoice
            $block->getMassactionBlock()->addItem(
                'paymentoperator_create_invoice',
                array(
                    'label' => $this->_getHelper()->__('CT: Create Invoice'),
                    'url'   => $block->getUrl('paymentoperator/adminhtml_massaction/massInvoice'),
                )
            );

            //Add massaction create invoice and ship
            $block->getMassactionBlock()->addItem(
                'paymentoperator_create_invoice_shipment',
                array(
                    'label' => $this->_getHelper()->__('CT: Create Invoice and Shipment'),
                    'url'   => $block->getUrl('paymentoperator/adminhtml_massaction/massInvoiceShipment'),
                )
            );
        }
    }

    /**
     * Only can create online refunds.
     *
     * @param unknown_type $observer
     */
    public function onSalesOrderCreditmemoRefund($observer)
    {
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        $creditmemo = $observer->getCreditmemo();
        $order      = $creditmemo->getOrder();

        //If we use a non paymentoperator payment we don't need to process this
        if (!$order->getPayment()->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            return;
        }

        //If we use a paymentoperator payment we force to refund online only
        if (!$creditmemo->hasInvoiceId()) {
            Mage::throwException(
                $this->_getHelper()->__('You can only create a credit memo in referenced to an invoiced.')
            );
        }

        //Check for online credit memo
        if ($creditmemo->hasDoTransaction() && !$creditmemo->getDoTransaction()) {
            Mage::throwException(
                $this->_getHelper()->__('You can only create online credit memos.')
            );
        }

        //Check for online credit memo
        if ($creditmemo->getPaymentRefundDisallowed()) {
            Mage::throwException(
                $this->_getHelper()->__('The payment method not allow to refund.')
            );
        }
    }

    /**
     * Only can create online invoices.
     *
     * @param unknown_type $observer
     */
    public function onSalesOrderInvoicePay($observer)
    {
        /* @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoice    = $observer->getInvoice();
        $order      = $invoice->getOrder();

        //If we use a non paymentoperator payment we don't need to process this
        if (!$order->getPayment()->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            return;
        }

        //If we use a paymentoperator payment we force to refund online only
        if ($invoice->hasRequestedCaptureCase()
            && Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE != $invoice->getRequestedCaptureCase()
        ) {
            Mage::throwException(
                $this->_getHelper()->__(
                    'This order was paid with an paymentoperator payment. '
                    . 'You\'r only allowed to create online invoices.'
                )
            );
        }
    }

    /**
     * Extends the sales order grid with the RefNr from the payment.
     *
     * @param $observer
     */
    public function onSalesOrderResourceInitVirtualGridColumns($observer)
    {
        /* @var $resource Mage_Sales_Model_Mysql4_Order */
        $resource = $observer->getResource();

        //Add the paymentoperator_transaction_id from the payment
        $resource->addVirtualGridColumn(
            'paymentoperator_transaction_id',
            'sales/order_payment',
            array('entity_id' => 'parent_id'),
            'paymentoperator_transaction_id'
        );
    }

    public function onSalesOrderShipmentSaveWithPurchaseOnAccount(Varien_Event_Observer $observer)
    {
        //FIXME:
        /**
    	 * Check if purchase on account
    	 * set new status to order
    	 * hope that magento will save it right
    	 */
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getShipment();
        $currentMethodCode = $shipment->getOrder()->getPayment()->getMethod();
        $purchaseOnAccountMethodCode = Mage::getModel('paymentoperator/payment_purchaseonaccount')->getCode();

        if (null === $shipment->getId() && $currentMethodCode === $purchaseOnAccountMethodCode) {
            $shipment->getOrder()->setStatus(
                Dotsource_Paymentoperator_Model_Payment_Purchaseonaccount::STATUS_READY_CAPTURE_PURCHASE_ON_ACCOUNT
            );
        }
    }

    /**
     * Call for observer after
     *      Mage_Checkout_OnepageController::saveBillingAction()
     *
     */
    public function saveBillingAddressCheck(Varien_Event_Observer $observer)
    {
        Mage::getModel('paymentoperator/check_address_address')->process();
    }

    /**
     * Active a global flag to show the redirect message by
     * the payment info block if needed.
     *
     * @param $observer
     */
    public function activeInfoPaymentRedirectMessage(Varien_Event_Observer $observer)
    {
        Mage::register('_paymentoperator_info_block_show_redirect_msg', true, true);
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