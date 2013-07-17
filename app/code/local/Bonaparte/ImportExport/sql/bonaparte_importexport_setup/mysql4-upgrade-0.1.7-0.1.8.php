<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('bonaparte_tmp_import_stock')};
CREATE TABLE {$this->getTable('bonaparte_tmp_import_stock')} (
  `sku` varchar(64),
  `qty` decimal(12, 4),
  `traffic_light` INT(2),
  `entity_id` int(10),
  `is_in_stock` smallint(5),
  PRIMARY KEY (`sku`),
  KEY (`entity_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
");



$installer->endSetup();
