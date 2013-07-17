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

$this->run("ALTER TABLE `{$this->getTable('paymentoperator_transaction')}` CHANGE `transaction_code` `transaction_code` VARCHAR(50) NOT NULL");
$this->run("ALTER TABLE `{$this->getTable('paymentoperator_transaction')}` ADD UNIQUE (`transaction_code`)");

$this->endSetup();