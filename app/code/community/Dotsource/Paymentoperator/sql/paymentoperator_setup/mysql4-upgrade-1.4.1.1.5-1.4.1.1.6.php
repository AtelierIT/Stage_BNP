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

$this->addAttribute('quote_payment', 'cc_prefill_information', array('type'=>'varchar'));

$this->endSetup();