<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('bonaparte_tmp_upd_links')};
CREATE TABLE {$this->getTable('bonaparte_tmp_upd_links')} (
  `link_id` int(10),
  `product_id` int(10),
  `linked_product_id` int(10),
  `no_order` int(10),
  PRIMARY KEY (`link_id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
");



$installer->endSetup();
