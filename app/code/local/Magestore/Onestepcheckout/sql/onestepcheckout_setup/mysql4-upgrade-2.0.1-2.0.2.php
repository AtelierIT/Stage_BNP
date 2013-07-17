<?php
/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_Geoip
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * create geoip table
 */
$installer->run("

ALTER TABLE {$this->getTable('geoip_country')} 
	ADD `first_ip_number` bigint unsigned,
	ADD `last_ip_number` bigint unsigned,
	ADD `first_ip_number_lower` bigint unsigned,
	ADD `last_ip_number_lower` bigint unsigned;

UPDATE geoip_country SET `first_ip_number` =  (CAST(SUBSTRING_INDEX(`first_ip` , '.', 1 ) AS UNSIGNED) * 16777216 + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`first_ip` , '.', 2),'.',-1) AS UNSIGNED) * 65536 + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`first_ip` , '.', -2),'.',1) AS UNSIGNED) * 256 + CAST(SUBSTRING_INDEX(`first_ip` , '.', -1) AS UNSIGNED)) WHERE `first_ip` LIKE '%.%';
UPDATE geoip_country SET `last_ip_number` =  (CAST(SUBSTRING_INDEX(`last_ip` , '.', 1 ) AS UNSIGNED) * 16777216 + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`last_ip` , '.', 2),'.',-1) AS UNSIGNED) * 65536 + CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`last_ip` , '.', -2),'.',1) AS UNSIGNED) * 256 + CAST(SUBSTRING_INDEX(`last_ip` , '.', -1) AS UNSIGNED)) WHERE `last_ip` LIKE '%.%';
UPDATE geoip_country SET `first_ip_number` =  (CONV(CAST(SUBSTRING_INDEX(`first_ip` , ':', 1 ) AS UNSIGNED),16,10) * POW(2,48) + CONV(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(`first_ip` , ':', 2),':',-1) AS UNSIGNED),16,10) * 4294967296) WHERE `first_ip` LIKE '%:%';
UPDATE geoip_country SET `last_ip_number` =  (CONV(SUBSTRING_INDEX(SUBSTRING_INDEX(`last_ip` , ':', 4),':',1),16,10) * POW(2,48) + CONV(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(`last_ip` , ':', 4),':',2),':',-1),16,10) * 4294967296 + CONV(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(`last_ip` , ':', 4),':',-2),':',1),16,10) * 65536 + CONV(SUBSTRING_INDEX(SUBSTRING_INDEX(`last_ip` , ':', 4),':',-1),16,10)) WHERE `last_ip` LIKE '%:%';
UPDATE geoip_country SET `last_ip_number_lower` =  (18446744073709551615) WHERE `last_ip` LIKE '%:%';
");


$installer->endSetup();
