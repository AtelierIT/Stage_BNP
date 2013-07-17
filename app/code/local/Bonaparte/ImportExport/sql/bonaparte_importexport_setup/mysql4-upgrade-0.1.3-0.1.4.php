<?php

$installer = $this;

$installer->startSetup();
$connection = $installer->getConnection();
$connection->addColumn($this->getTable('bonaparte_importexport_external_relation_attribute_option'), 'attribute_code', 'varchar(255)');

$installer->endSetup();