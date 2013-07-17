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

$this->addAttribute(
    'customer',
    'paymentoperator_risk_check',
    array(
        'type'  => 'text',
    )
);

$this->endSetup();