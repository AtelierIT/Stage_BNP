<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Rewrite_Sales_Order_Payment
    extends Mage_Sales_Model_Order_Payment
{

    /**
     * @see Mage_Sales_Model_Order_Payment::cancel()
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    public function cancel()
    {
        //We need a other logic for cancel processing
        if (!$this->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            parent::cancel();
        } else if ($this->getMethodInstance()->canVoid(new Varien_Object())) {
            $this->_void(true);
            Mage::dispatchEvent(
                'sales_order_payment_void',
                array('payment' => $this, 'invoice' => new Varien_Object())
            );
        } else {
            //Call the cancel method
            $this->getMethodInstance()->cancel($this);
            Mage::dispatchEvent('sales_order_payment_cancel', array('payment' => $this));
        }

        return $this;
    }


    /**
     * @see Mage_Sales_Model_Order_Payment::canCapture()
     *
     * @return unknown
     */
    public function canCapture()
    {
        //We need a other logic for cancel processing
        if (!$this->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            return parent::canCapture();
        }

        return $this->getMethodInstance()->canCapture();
    }


    /**
     * @see Mage_Sales_Model_Order_Payment::canVoid()
     *
     * @param   Varien_Object   $document
     * @return  boolean
     */
    public function canVoid(Varien_Object $document)
    {
        //We need a other logic for cancel processing
        if (!$this->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            return parent::canVoid($document);
        }

        return $this->getMethodInstance()->canVoid($document);
    }


    /**
     * Don't append transaction information to the order comment.
     *
     * @param   Mage_Sales_Model_Order_Payment_Transaction|null $transaction
     * @param   string                                          $message
     * @return  string
     */
    protected function _appendTransactionToMessage($transaction, $message)
    {
        //Call the default logic for non paymentoperator payment
        if (!$this->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            return parent::_appendTransactionToMessage($transaction, $message);
        }

        return $message;
    }

    /**
     * Return the last capture transaction.
     *
     * @return  Mage_Sales_Model_Order_Payment_Transaction|false
     */
    public function getLastCaptureTransaction()
    {
        return $this->_lookupTransaction(
            null,
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
        );
    }

    /**
     * Return true if the last capture transaction is closed. A capture
     * transaction will only closed on full refunding.
     *
     * @return  boolean
     */
    public function isLastCaptureTransactionClosed()
    {
        $captureTransaction = $this->getLastCaptureTransaction();
        if ($captureTransaction) {
            return (boolean) $captureTransaction->getIsClosed();
        }
    }
}