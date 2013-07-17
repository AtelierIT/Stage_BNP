<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 11.01.2011 09:54:57
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Purchaseonaccount
    extends Mage_Payment_Model_Method_Abstract
{
    const STATUS_WAITING_SHIPPING_PURCHASE_ON_ACCOUNT   = 'waiting_ship_purchase_acc';
    const STATUS_READY_CAPTURE_PURCHASE_ON_ACCOUNT      = 'ready_capture_purchase_acc';

    /** Create invoice during process of new order */
    protected $_canCapture          = true;

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_purchase_on_account';

    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_purchaseonaccount';

    /**
     * Set capture for payment-invoice depending on order status.
     *
     * case: Shipping   - is before capturing
     * case: Capturing  - is the last step
     * case: All other  - during checkout
     *
     * @param Varien_Object $payment
     * @param float $amount
     */
    public function capture(Varien_Object $payment, $amount)
    {
        switch ($payment->getOrder()->getStatus()) {
            case self::STATUS_WAITING_SHIPPING_PURCHASE_ON_ACCOUNT:
                throw new Mage_Core_Exception(
                    Mage::helper('sales')->__('Invoice couldn\'t be captured. Please set shipment first.')
                );
                break;

            case self::STATUS_READY_CAPTURE_PURCHASE_ON_ACCOUNT:
                // Do nothing, Magento will handle the result itself and set it to complete.
                break;

            default:
                $payment->setIsTransactionPending(true);
                $payment->setTransactionPendingState(Mage_Sales_Model_Order::STATE_PROCESSING);
                $payment->setTransactionPendingStatus(self::STATUS_WAITING_SHIPPING_PURCHASE_ON_ACCOUNT);
                $payment->setTransactionPendingMessage(
                    Mage::helper('sales')->__(
                        'Capturing amount of %s is pending.',
                        $payment->getOrder()->getBaseCurrency()->formatTxt($amount)
                    )
                );
                break;
        }
    }
}