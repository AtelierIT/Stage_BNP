<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Observer_Billpay_Billpay
{

    /**
     * Extend the payment hash.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function extendPaymentHash(Varien_Event_Observer $observer)
    {
        /* @var $orcale Dotsource_Paymentoperator_Model_Oracle_Type_Order */
        $transferObject = $observer->getTransferObject();
        $orcale         = $observer->getOracle();
        $model          = $orcale->getModel();
        $payment        = $model->getPayment();
        $paymentMethod  = $payment->getMethodInstance();

        //Only process billpay payment methods
        if (!$paymentMethod instanceof Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract) {
            return;
        }

        //Add the addresses to the payment hash
        $addresses = array(
            'billing_address' => $model->getBillingAddress(),
        );

        //If the shipping address not equals the billing address we also need the shipping address
        if (!$orcale->isShippingAddressEqualToBillingAddress()) {
            $addresses['shipping_address'] = $model->getShippingAddress();
        }

        //Holds the fields for the hash
        static $fields = array(
            'region_id',
            'region',
            'postcode',
            'lastname',
            'street',
            'city',
            'country_id',
            'firstname',
            'prefix',
            'middlename',
            'suffix',
            'company',
        );

        //Add all fields
        foreach ($addresses as $prefix => $address) {
            foreach ($fields as $field) {
                $transferObject->data["{$prefix}_$field"] = strtolower(trim($address->getData($field)));
            }
        }
    }

    /**
     * Sync the billpay transaction id to the payment information.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function syncBillpayTransactionId(Varien_Event_Observer $observer)
    {
        /* @var $method     Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract */
        /* @var $request    Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Abstract */
        /* @var $response   Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract */
        $method         = $observer->getMethod();
        $request        = $observer->getRequest();
        $response       = $observer->getResponse();
        $paymentInfo    = $method->getInfoInstance();

        //Set the transaction id to the payment informations
        $paymentInfo->setData(
            Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_TRANSACTION_ID,
            $response->getBillpayTransactionId()
        );
    }

    /**
     * Sync the response parameters to the payment informations.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function syncReciverPaymentInformation(Varien_Event_Observer $observer)
    {
        /* @var $method     Dotsource_Paymentoperator_Model_Payment_Billpay_Purchaseonaccount */
        /* @var $request    Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Purchaseonaccount_Authorize */
        /* @var $response   Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Purchaseonaccount_Authorize */
        $method         = $observer->getMethod();
        $request        = $observer->getRequest();
        $response       = $observer->getResponse();
        $paymentInfo    = $method->getInfoInstance();

        //Add all needed payment informations
        $paymentInfo->addData(
            array(
                Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_EFT_OWNER         => $response->getEftReceiverOwner(),
                Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_EFT_BAN_ENC       => $paymentInfo->encrypt($response->getEftReceiverBan()),
                Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_EFT_BCN           => $response->getEftReceiverBcn(),
                Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_EFT_BANK_NAME     => $response->getEftReceiverBankName(),
                Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_EFT_INVOICE_REF   => $response->getEftReceiverInvoiceReference(),
                Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_EFT_INVOICE_DATE  => $response->getEftReceiverInvoiceDate(),
            )
        );
    }

    /**
     * Create the invoice an the shipment.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onShipmentCreate(Varien_Event_Observer $observer)
    {
        /* @var $shipment       Mage_Sales_Model_Order_Shipment */
        /* @var $order          Mage_Sales_Model_Order */
        /* @var $paymentMethod  Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract */
        /* @var $orderItem      Mage_Sales_Model_Order_Item */
        /* @var $shipmentItem   Mage_Sales_Model_Order_Shipment_Item */
        $shipment       = $observer->getShipment();
        $order          = $shipment->getOrder();
        $payment        = $order->getPayment();
        $paymentMethod  = $payment->getMethodInstance();

        //Only process the billpay payment with the right order status
        if (!$paymentMethod instanceof Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract
            || Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::READY_FOR_BILLPAY_CAPTURE !== $order->getStatus()
        ) {
            return;
        }

        //Check create order ability
        if (!$order->canInvoice()) {
            Mage::throwException($this->_getHelper()->__("The order does not allow creating an invoice."));
        }

        //Check if a full shipment was created if the payment method dose not allow a partial capture
        if (!$paymentMethod->canCapturePartial()) {
            $orderedProductsMap = array();
            foreach ($order->getAllVisibleItems() as $orderItem) {
                $orderedProductsMap[$orderItem->getId()] = $orderItem->getQtyToInvoice();
            }

            foreach ($shipment->getAllItems() as $shipmentItem) {
                $orderItemId = $shipmentItem->getOrderItemId();
                $shipmentQty = $shipmentItem->getQty();

                //Check if the item exists an the qty are the same
                if (!isset($orderedProductsMap[$orderItemId]) || $orderedProductsMap[$orderItemId] != $shipmentQty) {
                    break;
                }

                //Unset for success
                unset($orderedProductsMap[$orderItemId]);
            }

            //If the array is not empty this means the qty is wrong or the order item was not in the shipment
            if ($orderedProductsMap) {
                Mage::throwException($this->_getHelper()->__("It's only possible to create a full shipment without an previous created invoice."));
            }
        }

        //Create the full invoice
        $invoice = $order->prepareInvoice();

        //Check for a valid invoice
        if (!$invoice || !$invoice->getTotalQty()) {
            Mage::throwException($this->_getHelper()->__("Can't create a valid invoice."));
        }

        //Set the capture case to online and register the invoice
        $invoice
            ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
            ->setPaymentoperatorAutomaticallyCreatedFlag(true)
            ->register();

        //Save the invoice
        $invoice
            ->setEmailSent($shipment->getEmailSent())
            ->save();

        //Send an email?
        if ($invoice->getEmailSent()) {
            try {
                $invoice->sendEmail(true);
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
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