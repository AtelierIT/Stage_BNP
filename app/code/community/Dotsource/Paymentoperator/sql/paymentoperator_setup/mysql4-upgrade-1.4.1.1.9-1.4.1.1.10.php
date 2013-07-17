<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 04.02.2011 10:27:26
 *
 * Contributors:
 * mdaehnert - initial contents
 */
/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */

$this->startSetup();

$this->addAttribute('quote_payment', 'klarna_dob', array('type'=>'datetime'));
$this->addAttribute('order_payment', 'klarna_dob', array('type'=>'datetime'));

$this->addAttribute('quote_payment', 'klarna_gender', array('type'=>'varchar'));
$this->addAttribute('order_payment', 'klarna_gender', array('type'=>'varchar'));

$this->addAttribute('quote_payment', 'klarna_ssn', array('type'=>'varchar'));
$this->addAttribute('order_payment', 'klarna_ssn', array('type'=>'varchar'));

$this->addAttribute('quote_payment', 'klarna_annual_salary', array('type'=>'varchar'));
$this->addAttribute('order_payment', 'klarna_annual_salary', array('type'=>'varchar'));

$this->endSetup();
