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

$this->run("ALTER TABLE `{$this->getTable('paymentoperator_transaction')}` CHANGE `entity_id` `entity_id` INT UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT;");
$this->run("ALTER TABLE `{$this->getTable('paymentoperator_transaction')}` DROP `transaction_code`;");

$this->endSetup();