<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 10.10.2012 16:34:23
 *
 * Contributors:
 * mwetter - initial contents
 */

/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

//Convert the old order status to the new one
$waitingAuth = substr('waiting_auth_paymentoperator_note', 0, 32);
$waitingCapture = substr('waiting_capture_paymentoperator_note', 0, 32);
$captureReady = substr('ready_paymentoperator_capture', 0, 32);

//update status table
$this->run("
    UPDATE {$this->getTable('sales_order_status')}
        SET status = 'waiting_auth_poperator_note'
    WHERE
        status LIKE '$waitingAuth%';

    UPDATE {$this->getTable('sales_order_status')}
        SET status = 'waiting_capture_poperator_note'
    WHERE
        status LIKE '$waitingCapture%';

    UPDATE {$this->getTable('sales_order_status')}
        SET status = 'ready_poperator_capture'
    WHERE
        status LIKE '$captureReady%';
");

$this->endSetup();