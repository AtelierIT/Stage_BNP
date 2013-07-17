<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Abstract
    extends Mage_Payment_Block_Info
{

    /**
     * Holds the types where the info block will displayed.
     * @var array
     */
    const CONTEXT_INVOICE       = 'invoice';
    const CONTEXT_CREDITMEMO    = 'creditmemo';
    const CONTEXT_SHIPMENT      = 'shipment';


    /**
     * Holds the mapping from the contexts to the stack frames. This
     * map will initialized by the static method _getContextMap().
     * @var array
     */
    protected static $_contextMap               = array();

    /**
     * Check if the context map was already initialized by a additional event.
     * @var boolean
     */
    protected static $_isContextMapInitialized  = false;


    /**
     * Return true if the current context is a invoice context.
     *
     * @return  boolean
     */
    public function isInvoiceContext()
    {
        return $this->_isContext(self::CONTEXT_INVOICE);
    }

    /**
     * Return true if the information is for the admin html (non secure mode)
     * or should shown in an invoice context.
     *
     * @return  boolean
     */
    public function isAdminOrInvoiceContext()
    {
        return !$this->getIsSecureMode() || $this->isInvoiceContext();
    }

    /**
     * Return true if the current context is a creditmemo context.
     *
     * @return  boolean
     */
    public function isCreditmemoContext()
    {
        return $this->_isContext(self::CONTEXT_CREDITMEMO);
    }

    /**
     * Return true if the current context is a shipment context.
     *
     * @return  boolean
     */
    public function isShipmentContext()
    {
        return $this->_isContext(self::CONTEXT_SHIPMENT);
    }

    /**
     * Return true if the given context is active.
     *
     * @param   string  $context
     * @return  boolean
     */
    protected function _isContext($context)
    {
        $contextMap = $this->_getContextMap();
        if (empty($contextMap[$context])) {
            return false;
        }

        return Dotsource_Paymentoperator_Model_Stacktrace::matchOneOf($contextMap[$context]);
    }

    /**
     * Return the context map.
     *
     * @return  array
     */
    protected static function _getContextMap()
    {
        if (false === self::$_isContextMapInitialized) {
            self::$_isContextMapInitialized = true;

            //Add default invoice context
            self::$_contextMap[self::CONTEXT_INVOICE]       = array(
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Pdf_Invoice')
                    ->setMethodName('getPdf'),
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Invoice')
                    ->setMethodName('sendEmail'),
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Invoice')
                    ->setMethodName('sendUpdateEmail'),
            );

            //Add default creditmemo context
            self::$_contextMap[self::CONTEXT_CREDITMEMO]    = array(
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Pdf_Creditmemo')
                    ->setMethodName('getPdf'),
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Creditmemo')
                    ->setMethodName('sendEmail'),
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Creditmemo')
                    ->setMethodName('sendUpdateEmail'),
            );

            //Add default shipment context
            self::$_contextMap[self::CONTEXT_SHIPMENT]      = array(
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Pdf_Shipment')
                    ->setMethodName('getPdf'),
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Shipment')
                    ->setMethodName('sendEmail'),
                Mage::getModel('paymentoperator/stacktrace_singleframe')
                    ->setObject('Mage_Sales_Model_Order_Shipment')
                    ->setMethodName('sendUpdateEmail'),
            );

            //Build the transfer object
            $transport = new Varien_Object(self::$_contextMap);

            //Give a chance to extend the class map
            Mage::dispatchEvent(
                'paymentoperator_initialize_payment_info_block_pdf_class_map',
                array(
                    'classes'   => $transport,
                )
            );

            //Store the result
            self::$_contextMap = $transport->getData();
        }

        return self::$_contextMap;
    }
}