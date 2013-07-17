<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 01.02.2011 01:05:25
 *
 * Contributors:
 * mdaehnert - initial contents
 */

$this->startSetup();

$this->addAttribute(
    'order_address',
    'paymentoperator_checked_address_hash',
    array('type' => 'varchar')
);

$this->endSetup();
