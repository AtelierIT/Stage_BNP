<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 24.01.2011 11:24:43
 *
 * Contributors:
 * mdaehnert - initial contents
 */

/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

$this->addAttribute(
    'quote_address',
    'po_checked_address_hash',
    array('type' => 'varchar')
);

$this->addAttribute(
    'customer_address',
    'po_checked_address_hash',
    array('type' => 'varchar')
);

$this->endSetup();