<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('bonaparte_tmp_import_prices')};
CREATE TABLE {$this->getTable('bonaparte_tmp_import_prices')} (
  `sku` varchar(64),
  `price` decimal(12,4),
  `special_price` decimal(12,4),
  `special_from_date` datetime,
  `special_to_date` datetime,
  `store_id` smallint(5),
  `bnp_adcodes` varchar(255),
  `entity_id` int(10),
  `skuc` varchar(64),
  `entity_id_c` int(10),
  `bnp_trafficlight` int(2),
  `bnp_pricecat` int(5),
  PRIMARY KEY (`sku`, `store_id`),
  KEY (`entity_id`, `store_id`),
  KEY (`skuc`),
  KEY (`entity_id_c`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

");

$installer->endSetup();
