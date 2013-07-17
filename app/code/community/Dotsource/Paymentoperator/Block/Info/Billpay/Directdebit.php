<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Billpay_Directdebit
    extends Dotsource_Paymentoperator_Block_Info_Billpay_Abstract
{

    /**
     * Add a new block to show the additional payment informations.
     */
    protected function _toHtml()
    {
        //Build the block
        $block = Mage::app()->getLayout()->createBlock("paymentoperator/info_billpay_directdebit_infotext");

        //Set the block as child
        if ($this->getIsSecureMode() && $this->isInvoiceContext()) {
            $this->setChild('paymentoperator_billpay_ratepay_directdebit_infotext', $block);
        }

        return parent::_toHtml();
    }

    /**
     * Return the payment informations.
     *
     * @param   Varien_Object|null   $transport
     * @return  Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        //Return the already cached payment information if available
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        /* @var $method Dotsource_Paymentoperator_Model_Payment_Billpay_Directdebit */
        $transport      = parent::_prepareSpecificInformation($transport);
        $method         = $this->getInfo()->getMethodInstance();
        $paymentHelper  = Mage::helper('payment');

        //Show the customer bank information
        $transport[$paymentHelper->__('Account holder')]        = $method->getEftOwner();
        $transport[$paymentHelper->__('Bank account number')]   = sprintf('xxxx-%s', $method->getEftBan3());
        $transport[$paymentHelper->__('Bank code number')]      = $method->getEftBcn();

        return $transport;
    }
}