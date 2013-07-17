<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 10.10.2012 15:28:11
 *
 * Contributors:
 * mwetter - initial contents
 */

/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

//Use the right table
$orderTable = $this->getFlatTableName('order');
if (null === $orderTable) {
    $orderTable = 'sales_order';
}

//Convert the old order status to the new one
$waitingAuth = substr('waiting_auth_paymentoperator_note', 0, 32);
$waitingCapture = substr('waiting_capture_paymentoperator_note', 0, 32);
$captureReady = substr('ready_paymentoperator_capture', 0, 32);

//Update the old status to the new status to the length problems in flat_tables
$this->run("
    UPDATE {$this->getTable($orderTable)}
        SET status = 'waiting_auth_poperator_note'
    WHERE
        status LIKE '$waitingAuth%';

    UPDATE {$this->getTable($orderTable)}
        SET status = 'waiting_capture_poperator_note'
    WHERE
        status LIKE '$waitingCapture%';

    UPDATE {$this->getTable($orderTable)}
        SET status = 'ready_poperator_capture'
    WHERE
        status LIKE '$captureReady%';
");

//Update order history comments
$orderHistoryTable = $this->getFlatTableName('order_status_history');
if (null !== $orderHistoryTable) {
    //Update the old status to the new status to the length problems in flat_tables
    $this->run("
        UPDATE {$this->getTable($orderHistoryTable)}
            SET status = 'waiting_auth_poperator_note'
        WHERE
            status LIKE '$waitingAuth%';

        UPDATE {$this->getTable($orderHistoryTable)}
            SET status = 'waiting_capture_poperator_note'
        WHERE
            status LIKE '$waitingCapture%';

        UPDATE {$this->getTable($orderHistoryTable)}
            SET status = 'ready_poperator_capture'
        WHERE
            status LIKE '$captureReady%';
    ");
} else {
    /* @var $attributeModel Mage_Eav_Model_Config */
    $attributeModel     = Mage::getModel('eav/config');
    $statusAttribute    = $attributeModel->getAttribute('order_status_history', 'status');

    if ($statusAttribute) {
        //Get the data we need for the update
        $statusEntityTypeId = $statusAttribute->getEntityTypeId();
        $statusAttributeId  = $statusAttribute->getAttributeId();

        $this->run("
            UPDATE {$statusAttribute->getBackendTable()}
                SET value = 'waiting_auth_poperator_note'
            WHERE
                value LIKE '$waitingAuth%' AND entity_type_id = $statusEntityTypeId AND attribute_id = $statusAttributeId;

            UPDATE {$statusAttribute->getBackendTable()}
                SET value = 'waiting_capture_poperator_note'
            WHERE
                value LIKE '$waitingCapture%' AND entity_type_id = $statusEntityTypeId AND attribute_id = $statusAttributeId;

            UPDATE {$statusAttribute->getBackendTable()}
                SET value = 'ready_poperator_capture'
            WHERE
                value LIKE '$captureReady%' AND entity_type_id = $statusEntityTypeId AND attribute_id = $statusAttributeId;
        ");
    }
}

$this->endSetup();