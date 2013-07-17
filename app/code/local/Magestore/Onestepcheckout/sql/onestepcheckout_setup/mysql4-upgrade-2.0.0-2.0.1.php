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

DROP TABLE IF EXISTS {$this->getTable('geoip')};

CREATE TABLE {$this->getTable('geoip')} (
  `geoip_id` int(11) unsigned NOT NULL auto_increment,
  `country` varchar(255) NOT NULL default '',
  `region` varchar(255),
  `city` varchar(255),
  `postcode` varchar(255),    
  PRIMARY KEY (`geoip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('onestepcheckout_admin')};

CREATE TABLE {$this->getTable('onestepcheckout_admin')} (
  `admin_id` int(11) unsigned NOT NULL auto_increment,
  `key` varchar(255) NOT NULL default '',
  `random` varchar(255),
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('geoip_country')};

CREATE TABLE {$this->getTable('geoip_country')} (
  `geoip_country_id` int(11) unsigned NOT NULL auto_increment,
  `first_ip` varchar(255),
  `last_ip` varchar(255),
  `country` varchar(255),  
  PRIMARY KEY (`geoip_country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('geoip_countrylist')};

CREATE TABLE {$this->getTable('geoip_countrylist')} (  
  `country_id` int(10) unsigned NOT NULL auto_increment,
  `country_code` varchar(255) NOT NULL default '',
  `country_name` varchar(255) NOT NULL default '',
  `status` smallint(5) NOT NULL default '0',
  `current_version` varchar(255),   
  `last_version` varchar(255) default '1.0',
  `type` smallint(5) default '0',
  `update_date` datetime, 
  `current_records` int(10) unsigned,
  `total_records`	int(10) unsigned,
  PRIMARY KEY (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE {$this->getTable('sales_flat_order')} 
	ADD COLUMN `onestepcheckout_discount_amount` DECIMAL(12,4) default NULL,   
	ADD COLUMN `onestepcheckout_admin_id` int(10) default NULL,   
	ADD COLUMN `onestepcheckout_admin_name` varchar(255) default NULL;   

");

$countrylist = Mage::getModel('onestepcheckout/countrylist');

$directories = Mage::getResourceModel('directory/country_collection');

foreach($directories as $directory){
	$countryCode = $directory->getData('country_id');		
		$total = 0;
		$fileUrl = 'version1.0'.DS.$countryCode.'.csv';						
		$oFile = new Varien_File_Csv();
		$url = Mage::getBaseDir().DS.'app'.DS.'locale'.DS.'magestore_geoip'.DS.'countrypostcode'.DS.$fileUrl;			
		try{
			$data = $oFile->getData($url);					
		}catch(Exception $e){
		}
		if(isset($data)){	
			$total = count($data);
		}	
	$countryName = Mage::app()->getLocale()->getCountryTranslation($countryCode);	
	try{
		$countrylist->setData('country_code', $countryCode)
				    ->setData('country_name', $countryName)
				    ->setData('total_records', $total-2)
				;				
		$countrylist->save()->setId(null);
	}catch(Exception $e){
		
	}
}
$countrylist = Mage::getModel('onestepcheckout/countrylist');	
$fileUrl = 'version1.0'.DS.'geoip.csv';
$url2 = Mage::getBaseDir().DS.'app'.DS.'locale'.DS.'magestore_geoip'.DS.'geoip'.DS.$fileUrl;			
	try{
		$data2 = $oFile->getData($url2);					
	}catch(Exception $e){
	}
	if(isset($data2)){	
		$total2 = count($data2);
	}	
try{
	$countrylist->setData('last_version', '1.0')
				->setData('type', 1)
				->setData('total_records', $total2-1)
				;				
	$countrylist->save()->setId(null);
}catch(Exception $e){
}

$installer->endSetup();
