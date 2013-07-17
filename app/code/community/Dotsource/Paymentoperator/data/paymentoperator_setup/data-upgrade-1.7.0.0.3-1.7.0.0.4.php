<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 16.10.2012 16:02:49
 *
 * Contributors:
 * mwetter - initial contents
 */
/**
 * rename entries in db from computop to paymentoperator
 * currently drop and delete queries uncommented to keep old entries
 */

//module names
$oldName = 'computop';
$oldLabel = 'Computop';
$newName = 'paymentoperator';
$newLabel = 'PaymentOperator';
$newShortName = 'poperator';
$newVeryShortName = 'po';

/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

//check and update db if module is installed
$module = $this->getConnection()->fetchAll("SELECT * FROM {$this->getTable('core_resource')} WHERE code = '{$oldName}_setup'");

if ($module) {
    /**
     * table: core_config_data
     *
     * rename entries for 'path' to keep configured system configurations
     */
    $this->run("UPDATE {$this->getTable('core_config_data')} SET path = REPLACE(path, '{$oldName}', '{$newName}') WHERE path LIKE '%{$oldName}%'");
    $this->run("UPDATE {$this->getTable('core_config_data')} SET value = REPLACE(value, '{$oldLabel}', '{$newLabel}') WHERE value LIKE '%{$oldLabel}%'");

    /**
     * table: sales_flat_order_address
     *
     * copy all data from <oldName>_checked_address_hash to <newName>_checked_address_hash
     * and drop column <oldName>_checked_address_hash
     */
    $this->run("UPDATE {$this->getTable('sales_flat_order_address')} SET {$newName}_checked_address_hash = {$oldName}_checked_address_hash");
//    $this->run("ALTER TABLE {$this->getTable('sales_flat_order_address')} DROP {$oldName}_checked_address_hash");

    /**
     * table: sales_flat_order_grid
     *
     * copy all data from <oldName>_transaction_id to <newName>_transaction_id
     * and drop column <oldName>_transaction_id afterwards
     */
    $this->run("UPDATE {$this->getTable('sales_flat_order_grid')} SET {$newName}_transaction_id = {$oldName}_transaction_id");
//    $this->run("ALTER TABLE {$this->getTable('sales_flat_order_grid')} DROP {$oldName}_transaction_id");

    /**
     * table: sales_flat_order_payment
     *
     * rename entries for 'method' and copy all data from
     * <oldName>_transaction_id to <newName>_transaction_id
     * and drop column <oldName>_transaction_id afterwards
     */
    $this->run("UPDATE {$this->getTable('sales_flat_order_payment')} SET method = REPLACE(method, '{$oldName}', '{$newName}') WHERE method LIKE '%{$oldName}%'");
    $this->run("UPDATE {$this->getTable('sales_flat_order_payment')} SET {$newName}_transaction_id = {$oldName}_transaction_id");
//    $this->run("ALTER TABLE {$this->getTable('sales_flat_order_payment')} DROP {$oldName}_transaction_id");

    /**
     * table: sales_flat_quote_address
     *
     * copy all data from <oldName>_checked_address_hash to <$newVeryShortName>_checked_address_hash
     * and drop column <oldName>_checked_address_hash afterwards
     */
    $this->run("UPDATE {$this->getTable('sales_flat_quote_address')} SET {$newVeryShortName}_checked_address_hash = {$oldName}_checked_address_hash");
//    $this->run("ALTER TABLE {$this->getTable('sales_flat_quote_address')} DROP {$oldName}_checked_address_hash");

    /**
     * table: sales_flat_quote_payment
     *
     * rename entries for 'method' and copy all data from
     * <oldName>_transaction_id to <newName>_transaction_id
     * and drop column <oldName>_transaction_id afterwards
     */
    $this->run("UPDATE {$this->getTable('sales_flat_quote_payment')} SET method = REPLACE(method, '{$oldName}', '{$newName}') WHERE method LIKE '%{$oldName}%'");
    $this->run("UPDATE {$this->getTable('sales_flat_quote_payment')} SET {$newName}_transaction_id = {$oldName}_transaction_id");
//    $this->run("ALTER TABLE {$this->getTable('sales_flat_quote_payment')} DROP {$oldName}_transaction_id");

    /**
     * table: sales_flat_order_status_history
     *
     * rename entries for 'status' and comment
     */
    $this->run("UPDATE {$this->getTable('sales_flat_order_status_history')} SET status = REPLACE(status, '{$oldName}', '{$newShortName}') WHERE status LIKE '%{$oldName}%'");
    $this->run("UPDATE {$this->getTable('sales_flat_order_status_history')} SET comment = REPLACE(comment, '{$oldLabel}', '{$newLabel}') WHERE comment LIKE '%{$oldLabel}%'");

    /**
     * table: <name>_transaction
     *
     * copy all entries from <oldName>_transaction to <newName>_transaction
     * and drop table <oldName>_transaction afterwards
     */
    $this->run("INSERT INTO {$this->getTable($newName . '_transaction')} SELECT * FROM {$this->getTable($oldName . '_transaction')}");
//    $this->run("DROP TABLE {$this->getTable($oldName . '_transaction')}");

    /**
     * table: <name>_action
     *
     * copy all entries from <oldName>_action to <newName>_action
     * and drop table <oldName>_transaction afterwards
     */
    $this->run("INSERT INTO {$this->getTable($newName . '_action')} SELECT * FROM {$this->getTable($oldName . '_action')}");
//    $this->run("DROP TABLE {$this->getTable($oldName . '_action')}");

    /**
     * table: eav_entity_text
     *
     * copy all entries with attibute id <oldName>_risk_check to <newName>_risk_check
     * and remove attribute <oldName>_risk_check afterwards
     */
    $rcOldAttributeId = $this->getAttributeId("customer", "{$oldName}_risk_check");
    $rcNewAttributeId = $this->getAttributeId("customer", "{$newName}_risk_check");
    $this->run("INSERT INTO {$this->getTable('eav_entity_text')}
        (entity_type_id, attribute_id, store_id, entity_id, value)
        SELECT eet.entity_type_id, {$rcNewAttributeId}, eet.store_id, eet.entity_id, eet.value
        FROM {$this->getTable('eav_entity_text')} AS eet
        WHERE attribute_id = {$rcOldAttributeId}");
//    $this->run("DELETE FROM {$this->getTable('eav_entity_text')} WHERE attribute_id = {$rcOldAttributeId}");

//    $this->removeAttribute("customer", "{$oldName}_risk_check");

    /**
     * table: eav_entity_varchar
     *
     * copy all entries with attibute id <oldName>_checked_address_hash to <newName>_checked_address_hash
     * and remove attribute <oldName>_checked_address_hash afterwards
     *
     */
    $cahOldAttributeId = $this->getAttributeId("customer_address", "{$oldName}_checked_address_hash");
    $cahNewAttributeId = $this->getAttributeId("customer_address", "{$newVeryShortName}_checked_address_hash");
    $this->run("INSERT INTO {$this->getTable('eav_entity_varchar')}
        (entity_type_id, attribute_id, store_id, entity_id, value)
        SELECT eet.entity_type_id, {$cahNewAttributeId}, eet.store_id, eet.entity_id, eet.value
        FROM {$this->getTable('eav_entity_text')} AS eet
        WHERE attribute_id = {$cahOldAttributeId}");
//    $this->run("DELETE FROM {$this->getTable('eav_entity_varchar')} WHERE attribute_id = {$cahOldAttributeId}");

//    $this->removeAttribute("customer_address", "{$oldName}_checked_address_hash");

    /**
     * table: sales_order_status
     *
     * delete old entries for order status
     */
//    $cWaitingAuth = substr('waiting_auth_computop_note', 0, 32);
//    $cWaitingCapture = substr('waiting_capture_computop_note', 0, 32);
//    $cCaptureReady = substr('ready_computop_capture', 0, 32);
//    $pWaitingAuth = substr('waiting_auth_paymentoperator_note', 0, 32);
//    $pWaitingCapture = substr('waiting_capture_paymentoperator_note', 0, 32);
//    $pCaptureReady = substr('ready_paymentoperator_capture', 0, 32);
//
//    //update status table
//    $this->run("
//        DELETE FROM {$this->getTable('sales_order_status')}
//            WHERE status LIKE '$cWaitingAuth%'
//            OR status LIKE '$pWaitingAuth%';
//
//        DELETE FROM {$this->getTable('sales_order_status')}
//            WHERE status LIKE '$cWaitingCapture%'
//            OR status LIKE '$pWaitingCapture%';
//
//        DELETE FROM {$this->getTable('sales_order_status')}
//            WHERE status LIKE '$cCaptureReady%'
//            OR status LIKE '$pCaptureReady%';
//    ");

    /**
     * table: core_resource
     *
     * delete old entry for sql setup
     */
//    $this->run("DELETE FROM {$this->getTable('core_resource')} WHERE code = '{$oldName}_setup'");
}

$this->endSetup();