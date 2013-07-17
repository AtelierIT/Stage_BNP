<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('bonaparte_importexport_example')};
CREATE TABLE {$this->getTable('bonaparte_importexport_example')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `description` varchar(100) NOT NULL,
  `other` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 
INSERT INTO {$this->getTable('bonaparte_importexport_example')} (name, description, other) values ('Example 1', 'Example One Description', 'This first example is reall awesome.');
INSERT INTO {$this->getTable('bonaparte_importexport_example')} (name, description, other) values ('Example 2', 'Example Two Description', 'This second example is reall awesome.');
INSERT INTO {$this->getTable('bonaparte_importexport_example')} (name, description, other) values ('Example 3', 'Example Three Description', 'This third example is reall awesome.');
INSERT INTO {$this->getTable('bonaparte_importexport_example')} (name, description, other) values ('Example 4', 'Example Four Description', 'This fourth example is reall awesome.');
 
");

$installer->endSetup();