<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Klarna_Refund
    extends Dotsource_Paymentoperator_Model_Payment_Request_Klarna_Abstract
{

    /** Holds the key for the creditmemo shipping amount key */
    protected $_creditmemoShippingKey = null;

    /** Holds the key for the order shipping key*/
    protected $_orderShippingKey = null;


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'credit.aspx';
    }


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        //Get the request object
        $encryptData    = $this->_getEncryptionDataObject();

        //Set the encrypt data
        $encryptData['TransID']     = $this->_getIncrementId();
        $encryptData['PayID']       = $this->getReferencedTransactionModel()->getAdditionalInformation('payid');

        if ($this->hasAmount()) {
            $encryptData['Amount']  = $this->_getConverter()->formatPrice($this->getAmount(), $this->_getCurrencyCode());
            $encryptData['Currency']= $this->_getConverter()->convertToUtf8($this->_getCurrencyCode());
        }

        $encryptData['OrderDesc']   = $this->_getOrderDesc();
    }


    /**
     * Return a valid order desc for klarna with the given items.
     *
     * @return string
     */
    protected function _getOrderDesc()
    {
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        $creditmemo = $this->getPayment()->getCreditmemo();

        //Valid the credit memo
        $this->_isValidCreditmemo($creditmemo);

        $currencyCode       = $this->_getCurrencyCode();
        $converter          = $this->_getConverter();
        $itemInformation    = array();

        //Process all items
        /* @var $item Mage_Sales_Model_Order_Creditmemo_Item */
        foreach ($creditmemo->getAllItems() as $item) {
            //Only process the real items -> without a parent
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }

            //Collect the informations we need to send
            $itemInformation[] = array(
                'amount'    => $item->getQty(),
                'sku'       => $item->getSku(),
            );
        }

        //Need to refund shipping?
        if ((float)$creditmemo[$this->_creditmemoShippingKey]) {
            $itemInformation[] = array(
                'amount'    => 1,
                'sku'       => self::SKU_SHIPPING
            );
        }

        return $this->_formatInformationArrayToOrderDesc($itemInformation);
    }


    /**
     * Check if we can process the credit memo configuration.
     *
     * @param Mage_Sales_Model_Order_Creditmemo || mixed $creditmemo
     */
    protected function _isValidCreditmemo($creditmemo)
    {
        //Valid credit memo?
        if (!$creditmemo
            || !$creditmemo instanceof Mage_Sales_Model_Order_Creditmemo
        ) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage('No valid credit memo available.');
        }

        //Valid invoice?
        $invoice = $creditmemo->getInvoice();
        if (!$invoice
            || !$invoice instanceof Mage_Sales_Model_Order_Invoice
        ) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage("It's only possible to create a refund to an related invoice.");
        }

        //The relating order
        $order          = $creditmemo->getOrder();
        $paymentHelper  = $this->_getHelper()->getPaymentHelper();

//        $this->__debugFields($creditmemo, 'shipp', '2.5');
//        $this->__debugFields($invoice, 'shipp', '2.5');
//        $this->__debugFields($order, 'shipp', '2.5');
//        die();

        //Magento is strange
        if (Mage::getSingleton('tax/config')->displaySalesShippingInclTax($order->getStoreId())) {
            $this->_creditmemoShippingKey  = 'shipping_incl_tax';
            $this->_orderShippingKey       = 'shipping_amount';
        } else {
            $this->_creditmemoShippingKey  = 'shipping_amount';
            $this->_orderShippingKey       = 'shipping_amount';
        }

        //Check for full shipping amount or no shipping amount
        if (!$this->_getHelper()->getPaymentHelper()->isZeroAmount($creditmemo[$this->_creditmemoShippingKey])) {
            $shippingDiff = $creditmemo[$this->_creditmemoShippingKey] - $order[$this->_orderShippingKey];
            if (!$this->_getHelper()->getPaymentHelper()->isZeroAmount($shippingDiff)) {
                $this->_getHelper()->getPaymentHelper()
                    ->getPaymentErrorManager('refund')
                    ->processMessage("It's not possible to refund a partial shipping amount. The order shipping amount is %s.", $order[$this->_orderShippingKey]);
            }
        }

        //It's not allowed to use adjustment
        $adjustmentFields = array('adjustment_positive', 'adjustment_negative');
        foreach ($adjustmentFields as $field) {
            if (!$paymentHelper->isZeroAmount($creditmemo[$field])) {
                $this->_getHelper()->getPaymentHelper()
                    ->getPaymentErrorManager('refund')
                    ->processMessage("It's not possible to adjust the refund amount. Only use the product amount to process the refund.");
            }
        }
    }


    /**
     * Debug method for display array/varien object information.
     *
     * @param $data
     * @param $keySub
     * @param $valueSub
     */
    private function __debugFields($data, $keySub = null, $valueSub = null)
    {
        if (is_object($data) && $data instanceof Varien_Object) {
            $data = $data->getData();
        }

        $found = false;
        foreach ($data as $key => $value) {
            //Check key
            if ($keySub && stripos($key, $keySub) === false) {
                continue;
            }

            //Check value
            if ($valueSub && stripos($value, $valueSub) === false) {
                continue;
            }

            echo "[$key] => $value<br />\n";
            $found = true;
        }

        if ($found) {
            echo "<br />\n";
        }
    }
}