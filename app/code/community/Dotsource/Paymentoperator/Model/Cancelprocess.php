<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Cancelprocess
{

    /** Holds the current payment */
    protected $_payment                     = null;

    /** Holds the online paid invoices */
    protected $_onlineInvoices              = null;

    /** Holds the online paid credit memos */
    protected $_onlineCreditmemos           = null;

    /** Holds to an invoice id all credit memo models */
    protected $_invoiceCreditmemosMap       = null;

    /** Maps the xid of every invoice as key */
    protected $_xidInvoiceMap               = null;

    /** Maps the xid of every credit memo as key */
    protected $_xidCreditmemoMap            = null;

    /** Holds all invoice referenced to the payid */
    protected $_payidInvoiceMap             = null;

    /** Holds all credit memo referenced to the payid */
    protected $_payidCreditmemoMap          = null;

    /** Holds all payids referenced to the invoice */
    protected $_invoicePayidMap             = null;

    /** Holds all payids referenced to the credit memo */
    protected $_creditmemoPayidMap          = null;

    /** Holds all open online transactions */
    protected $_onlineCaptureTransactions   = null;

    /** Holds all transactions with the payid as key */
    protected $_payidTransaction            = null;

    /** Holds the related objects for the order */
    protected $_needToSaveObject            = null;


    /**
     * Set the order payment.
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function setPayment(Mage_Sales_Model_Order_Payment $payment)
    {
        $this->_payment = $payment;
        return $this;
    }


    /**
     * Return the current payment.
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    protected function _getPayment()
    {
        return $this->_payment;
    }


    /**
     * Get the order from the current payment.
     *
     * @return Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        return $this->_getPayment()->getOrder();
    }


    /**
     * Return all online captured transactions.
     *
     * @return array
     */
    public function getOnlineCaptureTransaction()
    {
        if (null === $this->_onlineCaptureTransactions) {
            /* @var $captures Mage_Sales_Model_Mysql4_Order_Payment_Transaction_Collection */
            $captures = Mage::getResourceModel('sales/order_payment_transaction_collection')
                ->addPaymentIdFilter($this->_getPayment()->getId())
                ->addTxnTypeFilter(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

            //We only need open captures
            $captures->getSelect()->where('is_closed=?', 0);

            $this->_onlineCaptureTransactions = $captures->getItems();
        }

        return $this->_onlineCaptureTransactions;
    }


    /**
     * Create a full credit memo for the given invoice and the amount.
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function createFullCreditmemoFromInvoice(Mage_Sales_Model_Order_Invoice $invoice)
    {
        //Get the return able invoice
        $amount = $this->getRefundableAmountFromInvoice($invoice);

        //Nothing to do
        if ($this->isAmountZero($amount)) {
            return null;
        }

        //Create a converter object and create a credit memo
        /* @var $converter Mage_Sales_Model_Convert_Order */
        $converter  = Mage::getModel('sales/convert_order');
        $creditmemo = $converter->toCreditmemo($this->_getOrder());

        //Add all information to the credit memo
        $creditmemo
            ->setRefundRequested(true)
            ->setOfflineRequested(false)
            ->setInvoice($invoice);

        //Add all items to the credit memo from the invoice
        foreach ($invoice->getAllItems() as $invoiceItem) {
            /* @var $invoiceItem Mage_Sales_Model_Order_Invoice_Item */
            $orderItem = $invoiceItem->getOrderItem();

            if (!$orderItem->isDummy() && !$orderItem->getQtyToRefund()) {
                continue;
            }

            $item = $converter->itemToCreditmemoItem($orderItem);

            if ($orderItem->isDummy()) {
                $qty = 1;
            } else {
                $qty = min($orderItem->getQtyToRefund(), $invoiceItem->getQty());

                foreach ($this->getCreditmemosFromInvoiceId($invoice->getId()) as $onlineCreditmemo) {
                    foreach ($onlineCreditmemo->getAllItems() as $creditmemoItem) {
                        if ($orderItem->getId() == $creditmemoItem->getOrderItem()->getId()) {
                            $qty -= $creditmemoItem->getQty();
                        }
                    }
                }
            }

            $item
                ->setQty($qty)
                ->setBackToStock(false);

            $creditmemo->addItem($item);
        }

        //Collect the totals
        $creditmemo->collectTotals();

        //NOW! Don't use the magento calculated price the price is depends on the qyt
        //but you can refund money without depending on the items
        $diff = $amount - $creditmemo->getBaseGrandTotal();

        //If the difference is zero we no need to change something
        if (!$this->isAmountZero($diff)) {
            if ($this->_getHelper()->getPaymentHelper()->isNegativeAmount($diff)) {
                $creditmemo->setAdjustmentNegative(abs($diff));
            } elseif ($this->_getHelper()->getPaymentHelper()->isPositiveAmount($diff)) {
                $creditmemo->setAdjustmentPositive($diff);
            }

            //Recollect the totals
            $creditmemo
                ->setGrandTotal(0)
                ->setBaseGrandTotal(0)
                ->collectTotals();
        }

        return $creditmemo;
    }


    /**
     * Return the total amount who is online refundable.
     *
     * @return float
     */
    public function getTotalRefundableAmount()
    {
        $payidAmounts = $this->getRefundableAmountPerPayid();

        //Check if we have a amount for refunding
        if (!empty($payidAmounts)) {
            return array_sum($payidAmounts);
        }

        return 0.0;
    }


    /**
     * Return the available amount for every payid.
     *
     * @return array
     */
    public function getRefundableAmountPerPayid()
    {
        //Holds the refunds per payid
        $refunds = array();

        foreach ($this->getOnlinePaidInvoices() as $invoice) {
            $payid = $this->getPayidFromInvoiceId($invoice->getId());

            if (!array_key_exists($payid, $refunds)) {
                $refunds[$payid] = 0.0;
            }

            //Add the invoice amount
            $refunds[$payid] += (float)$invoice->getBaseGrandTotal();

            foreach ($this->getCreditmemosFromInvoiceId($invoice->getId()) as $creditmemo) {
                $refunds[$payid] -= (float)$creditmemo->getBaseGrandTotal();
            }

            //Unset the zero amounts
            if ($this->isAmountZero($refunds[$payid])) {
                unset($refunds[$payid]);
            }
        }

        return $refunds;
    }


    /**
     * Return the returnable invoice amount.
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return float
     */
    public function getRefundableAmountFromInvoice(Mage_Sales_Model_Order_Invoice $invoice)
    {
        //Get the invoice amount
        $amount = $invoice->getBaseGrandTotal();

        //Subtract the already refunded amount
        foreach ($this->getCreditmemosFromInvoiceId($invoice->getId()) as $creditmemo) {
            $amount -= (float)$creditmemo->getBaseGrandTotal();
        }

        return $amount;
    }


    /**
     * Return the the online paid invoices.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOnlinePaidInvoices()
    {
        if (null === $this->_onlineInvoices) {
            $items = array();

            //Collect all online paid invoices
            foreach ($this->_getOrder()->getInvoiceCollection() as $invoice) {
                if (Mage_Sales_Model_Order_Invoice::STATE_PAID == $invoice->getState()
                    && $invoice->hasTransactionId()
                ) {
                    $items[] = $invoice;
                    $this->_xidInvoiceMap[$invoice->getTransactionId()] = $invoice;
                }
            }

            $this->_onlineInvoices = $items;
        }

        return $this->_onlineInvoices;
    }


    /**
     * Return the the online refunded credit memos.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getOnlineRefundedCreditmemos()
    {
        if (null === $this->_onlineCreditmemos) {
            $items = array();

            //Collect all online refunded credit memos
            foreach ($this->_getOrder()->getCreditmemosCollection() as $creditmemo) {
                if (Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED == $creditmemo->getState()
                    && $creditmemo->hasTransactionId()
                    && $creditmemo->hasInvoiceId()
                ) {
                    $items[] = $creditmemo;
                    $this->_xidCreditmemoMap[$creditmemo->getTransactionId()] = $creditmemo;
                }
            }

            $this->_onlineCreditmemos = $items;
        }

        return $this->_onlineCreditmemos;
    }


    /**
     * Build the mapping from the invoice id to the referenced credit memos.
     *
     * @return Dotsource_Paymentoperator_Model_Cancelprocess
     */
    protected function _createInvoiceCreditmemoMapping()
    {
        if (null === $this->_invoiceCreditmemosMap) {
            foreach ($this->getOnlinePaidInvoices() as $invoice) {
                $this->_invoiceCreditmemosMap[$invoice->getId()] = array();

                foreach ($this->getOnlineRefundedCreditmemos() as $creditmemo) {
                    if ($invoice->getId() == $creditmemo->getInvoiceId()) {
                        $this->_invoiceCreditmemosMap[$invoice->getId()][] = $creditmemo;
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Return an array with all credit memos who are referenced with the given invoice id.
     *
     * @param int $invoiceId
     * @return array
     */
    public function getCreditmemosFromInvoiceId($invoiceId)
    {
        $this->_createInvoiceCreditmemoMapping();
        if (array_key_exists($invoiceId, $this->_invoiceCreditmemosMap)) {
            return $this->_invoiceCreditmemosMap[$invoiceId];
        }

        return array();
    }


    /**
     * Return the invoice to the given xid.
     *
     * @param string $xid
     * @return Mage_Sales_Model_Order_Invoice
     */
    public function getInvoiceFromXid($xid)
    {
        $this->getOnlinePaidInvoices();
        if (array_key_exists($xid, $this->_xidInvoiceMap)) {
            return $this->_xidInvoiceMap[$xid];
        }

        return null;
    }


    /**
     * Return the invoices to the given payid.
     *
     * @param string $payid
     * @return array
     */
    public function getInvoicesFromPayid($payid)
    {
        $this->_processPayidMapping();
        if (array_key_exists($payid, $this->_payidInvoiceMap)) {
            return $this->_payidInvoiceMap[$payid];
        }

        return array();
    }


    /**
     * Return the credit memo to the given xid.
     *
     * @param string $xid
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function getCreditmemoFromXid($xid)
    {
        $this->getOnlineRefundedCreditmemos();
        if (array_key_exists($xid, $this->_xidCreditmemoMap)) {
            return $this->_xidCreditmemoMap[$xid];
        }

        return null;
    }


    /**
     * Return the credit memos to the given payid.
     *
     * @param string $payid
     * @return array
     */
    public function getCreditmemosFromPayid($payid)
    {
        $this->_processPayidMapping();
        if (array_key_exists($payid, $this->_payidCreditmemoMap)) {
            return $this->_payidCreditmemoMap[$payid];
        }

        return array();
    }


    /**
     * Return the invoices to the given credit memo id.
     *
     * @param string $invoiceId
     * @return string || null
     */
    public function getPayidFromInvoiceId($invoiceId)
    {
        $this->_processPayidMapping();
        if (array_key_exists($invoiceId, $this->_invoicePayidMap)) {
            return $this->_invoicePayidMap[$invoiceId];
        }

        return null;
    }


    /**
     * Return the invoices to the given credit memo id.
     *
     * @param string $creditmemoId
     * @return string || null
     */
    public function getPayidFromCreditmemoId($creditmemoId)
    {
        $this->_processPayidMapping();
        if (array_key_exists($creditmemoId, $this->_creditmemoPayidMap)) {
            return $this->_creditmemoPayidMap[$creditmemoId];
        }

        return null;
    }


    /**
     * Build the mapping from the payid to the invoices and credit memos and the
     * invoice ids and credit memo ids to the payid.
     */
    protected function _processPayidMapping()
    {
        if (null === $this->_payidInvoiceMap
            || null === $this->_payidCreditmemoMap
            || null === $this->_invoicePayidMap
            || null === $this->_creditmemoPayidMap
            || null === $this->_payidTransaction
        ) {
            $this->_payidInvoiceMap     = array();
            $this->_payidCreditmemoMap  = array();
            $this->_invoicePayidMap     = array();
            $this->_creditmemoPayidMap  = array();
            $this->_payidTransaction    = array();
            $transactions = $this->getOnlineCaptureTransaction();

            foreach ($transactions as $transaction) {
                $payid  = $transaction->getAdditionalInformation('payid');
                $xid    = $transaction->getTxnId();

                if (!array_key_exists($payid, $this->_payidTransaction)) {
                    $this->_payidTransaction[$payid] = array();
                }

                 $this->_payidTransaction[$payid][] = $transaction;

                //Create invoice mapping
                foreach ($this->getOnlinePaidInvoices() as $invoice) {
                    if ($xid == $invoice->getTransactionId()) {
                        if (!array_key_exists($payid, $this->_payidInvoiceMap)) {
                            $this->_payidInvoiceMap[$payid] = array();
                        }

                        $this->_payidInvoiceMap[$payid][] = $invoice;
                        $this->_invoicePayidMap[$invoice->getId()] = $payid;

                        //Create credit memo mapping
                        foreach ($this->getOnlineRefundedCreditmemos() as $creditmemo) {
                            if ($invoice->getId() == $creditmemo->getInvoiceId()) {
                                if (!array_key_exists($payid, $this->_payidCreditmemoMap)) {
                                    $this->_payidCreditmemoMap[$payid] = array();
                                }

                                $this->_payidCreditmemoMap[$payid][] = $creditmemo;
                                $this->_creditmemoPayidMap[$creditmemo->getId()] = $payid;
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Return all online capture transactions with the given payid.
     *
     * @param string $payid
     * @return array
     */
    protected function getOnlineCaptureTransactionFromPayid($payid)
    {
        $this->_processPayidMapping();
        if (array_key_exists($payid, $this->_payidTransaction)) {
            return $this->_payidTransaction[$payid];
        }

        return array();
    }


    /**
     * Check if the given amount (float) is zero.
     *
     * @param float $amount
     * @return boolean
     */
    public function isAmountZero($amount)
    {
        return $this->_getHelper()->getPaymentHelper()->isZeroAmount($amount);
    }


    /**
     * Add the object in the intern related object array.
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _addRelatedObject(Mage_Core_Model_Abstract $object)
    {
        $this->_needToSaveObject[] = $object;
    }


    /**
     * Sync all intern related objects to the order.
     */
    public function syncRelatedObjects()
    {
        foreach ($this->_needToSaveObject as $object) {
            $this->_getOrder()->addRelatedObject($object);
        }

        $this->_needToSaveObject  = array();
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