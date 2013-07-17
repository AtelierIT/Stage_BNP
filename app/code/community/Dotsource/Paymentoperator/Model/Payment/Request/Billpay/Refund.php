<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Refund
    extends Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Abstract
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::getRequestFile()
     */
    public function getRequestFile()
    {
        return 'reverse.aspx';
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Request_Request::_getRequestData()
     */
    protected function _getRequestData()
    {
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        $encryptData                = $this->_getEncryptionDataObject();
        $creditmemo                 = $this->getPayment()->getCreditmemo();
        $converter                  = $this->_getHelper()->getConverter();

        //Check if we do an full refund
        $grandTotalDiff             = $this->getOracle()->getBaseGrandTotal() - $creditmemo->getBaseGrandTotal();

        //Set the request data
        $encryptData['TransID']     = $this->_getIncrementId();
        $encryptData['PayID']       = $this->getReferencedTransactionModel()->getAdditionalInformation('payid');
        $encryptData['Amount']      = $converter->formatPrice($this->getAmount(), $this->_getCurrencyCode());
        $encryptData['Currency']    = $converter->convertToUtf8($this->_getCurrencyCode());

        //We only need this field on partial refund
        if (!$this->_getHelper()->getPaymentHelper()->isZeroAmount($grandTotalDiff)) {
            if ($this->_getHelper()->getPaymentHelper()->isPositiveAmount($creditmemo->getBaseShippingAmount())) {
                $encryptData['shRebate']    = $converter->formatPrice(
                    $creditmemo->getBaseShippingAmount(),
                    $this->_getCurrencyCode()
                );
                $encryptData['shRebateGr']  = $converter->formatPrice(
                    $creditmemo->getBaseShippingAmount() + $creditmemo->getBaseShippingTaxAmount(),
                    $this->_getCurrencyCode()
                );
            }

            //Check if we have items
            if ($creditmemo->getAllItems()) {
                $encryptData['ArticleList'] = $this->_getArticleList();
            }
        }
    }

    /**
     * Return the article list informations.
     *
     * @return  string
     */
    protected function _getArticleList()
    {
        /* @var $creditmemo     Mage_Sales_Model_Order_Creditmemo */
        /* @var $item           Mage_Sales_Model_Order_Creditmemo_Item */
        /* @var $stringHelper   Mage_Core_Helper_String */
        $creditmemo         = $this->getPayment()->getCreditmemo();
        $items              = $creditmemo->getAllItems();
        $stringHelper       = Mage::helper('core/string');
        $itemInformations   = array();

        //collect the refund items
        foreach ($items as $item) {
            //Only process the visible items
            if ($item->getOrderItem()->getParentItemId()) {
                continue;
            }

            $itemInformations[] = array(
                'sku'       => $stringHelper->truncate($item->getSku(), 20),
                'amount'    => $item->getQty(),
            );
        }

        return $this->_formatInformation($itemInformations);
    }
}