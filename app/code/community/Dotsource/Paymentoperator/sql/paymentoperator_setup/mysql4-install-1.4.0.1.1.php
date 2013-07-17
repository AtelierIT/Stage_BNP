<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

//Add the new payment fields
$this->addAttribute('order_payment', 'paymentoperator_transaction_id', array('type' => 'int'));
$this->addAttribute('quote_payment', 'paymentoperator_transaction_id', array('type' => 'int'));

$this->addAttribute('order_payment', 'eft_owner', array('type'=>'varchar'));
$this->addAttribute('quote_payment', 'eft_owner', array('type'=>'varchar'));

$this->addAttribute('order_payment', 'eft_ban_enc', array('type'=>'varchar'));
$this->addAttribute('quote_payment', 'eft_ban_enc', array('type'=>'varchar'));

$this->addAttribute('order_payment', 'eft_ban4', array('type'=>'varchar'));
$this->addAttribute('quote_payment', 'eft_ban4', array('type'=>'varchar'));

$this->addAttribute('order_payment', 'eft_bcn', array('type'=>'varchar'));
$this->addAttribute('quote_payment', 'eft_bcn', array('type'=>'varchar'));

//Holds the table names
$tableAction      = $this->getTable('paymentoperator_action');
$tableTransaction = $this->getTable('paymentoperator_transaction');

//Transaction table
$this->run("
CREATE TABLE IF NOT EXISTS `$tableTransaction` (
  entity_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  transaction_code VARCHAR(32),
  PRIMARY KEY(entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
");

//Holds communication actions
$this->run("
CREATE TABLE IF NOT EXISTS `$tableAction` (
  entity_id INT NOT NULL AUTO_INCREMENT,
  transaction_id INT UNSIGNED NOT NULL,
  request_payid VARCHAR(32) NOT NULL,
  response_payid VARCHAR(32) NOT NULL,
  xid VARCHAR(64) NOT NULL,
  created_at DATETIME,
  action VARCHAR(24) NOT NULL,
  error CHAR(1) NOT NULL default 0,
  error_code CHAR(8) NOT NULL,
  error_description TEXT NOT NULL,
  PRIMARY KEY(entity_id),
  CONSTRAINT `FK_PAYMENTOPERATOR_TRANSACTION_ENTITY_ID`
    FOREIGN KEY (`transaction_id`)
    REFERENCES $tableTransaction (`entity_id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$this->endSetup();