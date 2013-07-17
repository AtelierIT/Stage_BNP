<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('bonaparte_importexport_external_relation_attribute_option')};
CREATE TABLE {$this->getTable('bonaparte_importexport_external_relation_attribute_option')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` varchar(100) NOT NULL,
  `external_id` varchar(100) NOT NULL,
  `internal_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");

$installer->endSetup();