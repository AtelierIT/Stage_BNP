<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_SALUTATION, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_SALUTATION, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_COMPANY_NAME, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_COMPANY_NAME, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_DOB, array('type'=>'date'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_DOB, array('type'=>'date'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_COMPANY_LEGAL_FORM, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_COMPANY_LEGAL_FORM, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_OWNER, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_OWNER, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_BAN_ENC, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_BAN_ENC, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_BAN3, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_BAN3, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_BCN, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_EFT_BCN, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_TERM, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_TERM, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_OWNER, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_OWNER, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_BAN_ENC, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_BAN_ENC, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_BCN, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_BCN, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_BANK_NAME, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_BANK_NAME, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_INVOICE_REF, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_INVOICE_REF, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_INVOICE_DATE, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_EFT_INVOICE_DATE, array('type'=>'varchar'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_PAYMENT_PLAN, array('type'=>'text'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Ratepay::KEY_BILLPAY_PAYMENT_PLAN, array('type'=>'text'));

$this->addAttribute('quote_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_TRANSACTION_ID, array('type'=>'varchar'));
$this->addAttribute('order_payment', Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract::KEY_BILLPAY_TRANSACTION_ID, array('type'=>'varchar'));

$this->endSetup();