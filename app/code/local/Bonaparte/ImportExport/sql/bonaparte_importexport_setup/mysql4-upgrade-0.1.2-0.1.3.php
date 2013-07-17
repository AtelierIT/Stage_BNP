<?php

$installer = $this;

$installer->startSetup();
$connection = $installer->getConnection();
$connection->addColumn($this->getTable('eav_attribute'), 'external_id', 'varchar(255)');

$installer->endSetup();