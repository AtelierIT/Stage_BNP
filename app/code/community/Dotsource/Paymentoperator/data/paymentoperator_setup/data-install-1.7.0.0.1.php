<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 09.10.2012 16:38:08
 *
 * Contributors:
 * mwetter - initial contents
 */

/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */

//Check if we can fill the order status tables
if (!Mage::helper('paymentoperator/feature')->hasOrderStatusTables()) {
    return;
}

//Start the installation
$this->startSetup();

//Tables we need since 1.5.X for the order status
$statusTable        = $this->getTable('sales/order_status');
$statusStateTable   = $this->getTable('sales/order_status_state');

//Collect the the already inserted statuses
$alreadyInserted    = array();
foreach (Mage::getResourceModel('sales/order_status_collection')->toOptionArray() as $option) {
    $alreadyInserted[$option['value']] = true;
}

//Collect new statuses and insert the new ones
$statuses           = Mage::getConfig()->getNode('global/sales/order/statuses')->asArray();
$data               = array();
foreach ($statuses as $code => $info) {
    if (empty($alreadyInserted[$code])) {
        $data[] = array(
            'status'    => $code,
            'label'     => $info['label']
        );
    }
}
if ($data) {
    $this->getConnection()->insertArray($statusTable, array('status', 'label'), $data);
}

//Collect the the already inserted states
$alreadyInserted    = array();
foreach (Mage::getResourceModel('sales/order_status_collection')->joinStates() as $option) {
    $option = $option->getData();
    $alreadyInserted[$option['status'].'#-#'.$option['state']] = true;
}

//Collect new statuses and insert the new ones
$data               = array();
$states             = Mage::getConfig()->getNode('global/sales/order/states')->asArray();
foreach ($states as $code => $info) {
    if (isset($info['statuses'])) {
        foreach ($info['statuses'] as $status => $statusInfo) {
            if(empty($alreadyInserted[$status.'#-#'.$code])) {
                $data[] = array(
                    'status'    => $status,
                    'state'     => $code,
                    'is_default'=> is_array($statusInfo) && isset($statusInfo['@']['default']) ? 1 : 0
                );
            }
        }
    }
}
if ($data) {
    $this->getConnection()->insertArray(
        $statusStateTable,
        array('status', 'state', 'is_default'),
        $data
    );
}

$this->endSetup();