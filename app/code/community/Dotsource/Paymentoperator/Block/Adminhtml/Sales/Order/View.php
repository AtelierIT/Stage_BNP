<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Adminhtml_Sales_Order_View
    extends Mage_Adminhtml_Block_Sales_Order_View
{

    public function __construct()
    {
        parent::__construct();

        //Only need for paymentoperator payment
        if (!$this->getOrder()->getPayment()->getMethodInstance() instanceof Dotsource_Paymentoperator_Model_Payment_Abstract) {
            return;
        }

        //Refresh the onclick message
        if ($this->_hasButton('order_cancel')) {
            $message = Mage::helper('paymentoperator')->__(
                'Are you sure you want to cancel this order and refund an amount of %s?',
                $this->getOrder()->getPayment()->getMethodInstance()->getCancelAmount($this->getOrder(), true)
            );

            $this->_updateButton('order_cancel', 'onclick', 'deleteConfirm(\''.$message.'\', \'' . $this->getCancelUrl() . '\')');
        }
    }


    /**
     * Return true if the given button already exists.
     *
     * @param string $id
     * @return boolean
     */
    protected function _hasButton($id)
    {
        foreach ($this->_buttons as $level => $buttons) {
            if (isset($buttons[$id])) {
                return true;
            }
        }

        return false;
    }


    /**
     * Return the button data from the given button id.
     *
     * @param string $id
     * @return array
     */
    protected function _getButtonData($id)
    {
        foreach ($this->_buttons as $level => $buttons) {
            if (isset($buttons[$id])) {
                return $buttons[$id];
            }
        }

        return array();
    }
}