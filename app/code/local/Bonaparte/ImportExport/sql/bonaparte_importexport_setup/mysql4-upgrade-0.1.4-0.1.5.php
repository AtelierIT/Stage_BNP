<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('bonaparte_importexport_catalogue')};
CREATE TABLE {$this->getTable('bonaparte_importexport_catalogue')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `suffix` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL COMMENT 'Catalogue StartDate',
  `end_date` date DEFAULT NULL COMMENT 'Catalogue EndDate',
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-BAP-AUT', 'M');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-BAP-PAUT', '5');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-BAP-PSPR', '1');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-BAP-SPR', 'M');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-BAP-SUM', '3');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-BAP-WIN', '4');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-FAV-PSPR', 'F');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-FAV-WIN', 'F');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-FAV2-SUM', 'L');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-IMAGE-PAUT', '5');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-KIDS-SUM', '0');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-KIDS-WIN', '7');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-OUTLET-SUM', 'G');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-OUTLET-WIN', 'O');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-PAKK1-SUM', 'P');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-PAKK1-WIN', 'P');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-PAKK2-SUM', 'P');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-PLUS-PAUT', '2');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-PLUS-PSPR', 'Z');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-SEC-SUM', '2');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-SEC-WIN', '6');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-UDS1-SUM', '9');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2013-UDS2-SUM', '8');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2014-BAP-PSPR', '1');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2014-KIDS-SUM', '0');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2014-PLUS-PSPR', 'Z');
INSERT INTO {$this->getTable('bonaparte_importexport_catalogue')} (name, suffix) values ('2014-SEC-SUM', '2');

");

$installer->endSetup();