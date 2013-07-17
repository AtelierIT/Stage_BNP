<?php

/**
 * Stores the business logic for the custom price import
 *
 * @category    Bonaparte
 * @package     Bonaparte_ImportExport
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_ImportExport_Model_Custom_Import_Prices extends Bonaparte_ImportExport_Model_Custom_Import_Abstract
{
    /**
     * Path at which the category configuration is found
     *
     * @var string
     */
    const CONFIGURATION_FILE_PATH = '/dump_files/WBN240S';
    const CONFIGURATION_FILE_PATH_TMP = '/dump_files/bonaparte_tmp_import_prices.csv';

    /**
     * The magento code for attibute adcodes1
     *
     * @var string
     */
    const ATTRIBUTE_CODE_ADCODES = 'bnp_adcodes';

    const MEMORY_LIMIT = '2048M';

    /**
     * Store current ad code for the sku
     *
     * @var array
     */
    private $_skuAdCodes = array();

    private $_priceRow = array ('price','reg_price');

    /**
     * Construct import model
     */
    public function _construct()
    {
        ini_set('memory_limit', self::MEMORY_LIMIT);

        $this->_logMessage('Reading configuration files');
        $this->_configurationFilePath = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH;
        $this->_configurationFilePathTmp = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH_TMP; //tmunteanu

        $fileHandler = fopen($this->_configurationFilePath, 'r');
        $prices = array();
        $dataKeys = array(
            'articleno',
            'headarticle',
            'countrycode',
            'articleno2',
            'headarticle2',
            'description',
            'size',
            'price',
            'stockdisp',
            'status',
            'length',
            'width',
            'height',
            'weight',
            'showcode',
            'timestamp',
            'metrecode',
            'waitlist',
            'reg_price',
            'traffic_light',
            'available_date',
            'preview'
        );

        $row = 0;
        $currentSKU = null;
        $temporaryData = array();
        while($line = fgets($fileHandler)) {
            $row++;
            //$this->_logMessage('Row ' . $row);
            $line = explode(';', $line);
            $priceData = array();
            foreach($dataKeys as $key => $value) {
                if (in_array($value,$this->_priceRow)) {
                    $priceData[$value] = intval($line[$key]);
                }else{
                    $priceData[$value] = $line[$key];
                }

            }
            unset($key, $value);

            $headArticleExploded = explode('-', $priceData['headarticle']);
            $rowSKU = $headArticleExploded[1] . '-' . $priceData['size'] . '-' . $priceData['countrycode'];
            if($currentSKU != $rowSKU) {
                if(count($temporaryData)) {
                    $lowestPrice = null;
                    foreach($temporaryData as $data) {
                        if(($data['price'] < $lowestPrice) || $lowestPrice === null) {
                            $lowestPrice = $data['price'];
                            $this->_skuAdCodes[$currentSKU] = substr($data['articleno2'], 5, 1);
                        }
                    }
                    $this->_data[$currentSKU] = array(
                        'regular' => $data['reg_price']?$data['reg_price']:$lowestPrice,
                        'special' => $lowestPrice,
                        'traffic_light' => $data['traffic_light']
                    );
                    unset($data, $lowestPrice);
                }

                $currentSKU = $rowSKU;
                unset($temporaryData);
            }

            $temporaryData[] = $priceData;
            unset($priceData);
        }
        fclose($fileHandler);
    }

    /**
     * Add price and special price to configurable product
     * @param $sku
     * @param $regularprice
     * @param $specialPrice
     */
//    public function _addPriceToConfigurableProduct($sku, $regularPrice, $specialPrice){
//        $model = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
//
//    }
    /**
     * Specific category functionality
     */
    public function start($options = array())
    {
        $this->_logMessage('Started importing prices' . "\n" );

        $storeViews = array();
        foreach(Mage::app()->getWebsites() as $website) {
            $storeIds = $website->getStoreIds();
            $storeViews[strtolower($website->getCode())] = array_pop($storeIds);
        }

        print_r($storeViews);
        echo "\n";

		$eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
		$conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connW = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $config  = Mage::getConfig()->getResourceConnectionConfig("default_setup");
        
        //Get attribute codes for price, special_price, special_from_date, special_to_date
		$attr_id_price         = $eavAttribute->getIdByCode('catalog_product', 'price');
		$attr_id_special_price = $eavAttribute->getIdByCode('catalog_product', 'special_price');
		$attr_id_special_from  = $eavAttribute->getIdByCode('catalog_product', 'special_from_date');
		$attr_id_special_to    = $eavAttribute->getIdByCode('catalog_product', 'special_to_date');
		$attr_id_status        = $eavAttribute->getIdByCode('catalog_product', 'status');
        $attr_id_traffic_light = $eavAttribute->getIdByCode('catalog_product', 'bnp_trafficlight');
        $attr_id_pricecat      = $eavAttribute->getIdByCode('catalog_product', 'bnp_pricecatalogue');
        $attr_id_bnp_adcodes   = $eavAttribute->getIdByCode('catalog_product', 'bnp_adcodes');

        // get the entity type id for product
		$entityType = Mage::getModel('eav/entity_type')->loadByCode('catalog_product');
		$entityTypeId = $entityType->getEntityTypeId();
		
		$baseDir = Mage::getBaseDir();
		
		#echo $config->username." -p".$config->password." ".$config->dbname;
		#echo "\n";
		
		$sql = "TRUNCATE bonaparte_tmp_import_prices;";
        $connW->query($sql);
        
        $fileHandler2 = fopen($this->_configurationFilePathTmp, 'w'); //tmunteanu
		
        $row = 0;
        foreach($this->_data as $sku => $price) {
			if ($row == 0) { $row++; continue;}
            $row++;

            #print_r($this->_skuAdCodes);

            //$this->_logMemoryUsage();
            $countrySku = $sku;
            $sku = explode('-', $sku);

            // skip first 12000 SKUs
            //if (intval($sku[0])<12000) continue;
            $configurableProductSKU = $sku[0] . 'c';
            $countryCode = strtolower($sku[2]);
            if ($sku[1] ){
                $sku = $sku[0] . '-' . $sku[1];
            }
            else $sku = $sku[0];


            #$this->_logMessage('Sku: ' . $sku . "\n" );
			/*
            $model = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
            if(empty($model)) {
                continue;
            }
			
            $relationCollection = Mage::getModel('Bonaparte_ImportExport/External_Relation_Attribute_Option')
                ->getCollection()
                ->addFieldToFilter('attribute_code', self::ATTRIBUTE_CODE_ADCODES)
                ->addFieldToFilter('external_id', $this->_skuAdCodes[$countrySku]);
            $relationModel = $relationCollection->load()->getFirstItem();
            
            echo $this->_skuAdCodes[$countrySku]. " ". $relationModel->getInternalId();
            */
            
            $sql = "SELECT * FROM bonaparte_importexport_external_relation_attribute_option WHERE attribute_code = '". self::ATTRIBUTE_CODE_ADCODES ."' AND external_id = '". $this->_skuAdCodes[$countrySku]."'";
            $results = $conn->fetchAll($sql);
            
            if ($results) $v_BnpAdcodes = $results[0]['internal_id'];
            else $v_BnpAdcodes = "";
            

            #$this->_logMessage('Sku: ' . $sku . ' on ' . $countryCode . "\n" );
            /* tmunteanu
            $model->setStoreId($storeViews[$countryCode])
                    ->setPrice($price['regular']/100)
                    ->setSpecialPrice($price['special']/100)
                    ->setBnpAdcodes($relationModel->getInternalId())
                    ->save();
            $model->clearInstance(); */
            
            fputcsv($fileHandler2,array($sku, $price['regular']/100, $price['special']/100, date('Y-m-d'), date('Y-m-d', strtotime('now + 1 month')), $storeViews[$countryCode], $v_BnpAdcodes, 0, $configurableProductSKU, 0, $price['traffic_light'], $this->_skuAdCodes[$countrySku]));

            #$relationModel->clearInstance();
            #unset($relationCollection);
            
        }
        fclose($fileHandler2);
        
        $this->_logMessage('Finished insert temp' . "\n" );
        
        /*
        $sql = "LOAD DATA LOCAL INFILE '/var/www/magento/dump_files/bonaparte_tmp_import_prices.csv' INTO TABLE bonaparte_tmp_import_prices FIELDS TERMINATED BY ','";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql); */
        
        if( ($fp = popen("mysql -u ".$config->username." -p".$config->password." ".$config->dbname." -e \"LOAD DATA LOCAL INFILE '$baseDir/dump_files/bonaparte_tmp_import_prices.csv' INTO TABLE bonaparte_tmp_import_prices FIELDS TERMINATED BY ',';\";", "r")) ) {
			echo "Done insert temp \n";
			while( !feof($fp) ){
				echo fread($fp, 1024);
				flush(); // you have to flush buffer
			}
			fclose($fp);
		}
        
        $sql = "UPDATE bonaparte_tmp_import_prices bt SET entity_id = (SELECT entity_id FROM catalog_product_entity WHERE sku = bt.sku), entity_id_c = (SELECT entity_id FROM catalog_product_entity WHERE sku = bt.skuc)";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);

        //update entity id for uk sku (sizes)
        $sql = "UPDATE bonaparte_tmp_import_prices bt SET entity_id = (SELECT DISTINCT simple_entity_id FROM bonaparte_styles bs WHERE uk_sku = bt.sku AND uk_sku <> '') WHERE IFNULL(entity_id,0) = 0 AND EXISTS (SELECT 1 FROM bonaparte_styles bs2 WHERE bs2.uk_sku <> '' AND bs2.uk_sku = bt.sku )";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);
        
		//update price
        #$sql = "UPDATE catalog_product_entity_decimal e SET value = (SELECT b.price FROM bonaparte_tmp_import_prices b WHERE b.entity_id = e.entity_id AND b.store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_price, store_id, entity_id FROM bonaparte_tmp_import_prices) ";
        $sql = "INSERT INTO catalog_product_entity_decimal (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_price, store_id, entity_id, price FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id,0) <> 0 ON DUPLICATE KEY UPDATE value = b.price";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);   
        
		//update special_price
        #$sql = "UPDATE catalog_product_entity_decimal e SET value = (SELECT special_price FROM bonaparte_tmp_import_prices WHERE entity_id = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_special_price, store_id, entity_id_c FROM bonaparte_tmp_import_prices) ";
        $sql = "INSERT INTO catalog_product_entity_decimal (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_special_price, store_id, entity_id, special_price FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id,0) <> 0 ON DUPLICATE KEY UPDATE value = b.special_price";

        $this->_logMessage($sql . "\n" );
        $connW->query($sql);    
        
		//update special_from_date
        #$sql = "UPDATE catalog_product_entity_datetime e SET value = (SELECT special_from_date FROM bonaparte_tmp_import_prices WHERE entity_id = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_special_from, store_id, entity_id FROM bonaparte_tmp_import_prices) ";
        $sql = "INSERT INTO catalog_product_entity_datetime (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_special_from, store_id, entity_id, special_from_date FROM bonaparte_tmp_import_prices b  WHERE IFNULL(b.entity_id,0) <> 0 ON DUPLICATE KEY UPDATE value = b.special_from_date";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);    
        
		//update special_to_date
        #$sql = "UPDATE catalog_product_entity_datetime e SET value = (SELECT special_to_date FROM bonaparte_tmp_import_prices WHERE entity_id = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_special_to, store_id, entity_id FROM bonaparte_tmp_import_prices) ";
        $sql = "INSERT INTO catalog_product_entity_datetime (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_special_to, store_id, entity_id, special_to_date FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id,0) <> 0  ON DUPLICATE KEY UPDATE value = b.special_to_date";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);

        //update bnp_trafficlight
        /*
        $sql = "UPDATE catalog_product_entity_int e SET value = (SELECT bnp_trafficlight FROM bonaparte_tmp_import_prices WHERE entity_id = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_traffic_light, store_id, entity_id FROM bonaparte_tmp_import_prices) ";
        $this->_logMessage("Sql bnp_trafficlight: " . $sql . "\n" );
        $connW->query($sql); */

        $sql = "INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_traffic_light, store_id, entity_id, bnp_trafficlight FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id,0) <> 0 ON DUPLICATE KEY UPDATE value = b.bnp_trafficlight";
        $this->_logMessage("Sql bnp_trafficlight: " . $sql . "\n" );
        $connW->query($sql);

        //update bnp_pricecatalogue
        /*
        $sql = "UPDATE catalog_product_entity_int e SET value = (SELECT bnp_pricecat FROM bonaparte_tmp_import_prices WHERE entity_id = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_pricecat, store_id, entity_id FROM bonaparte_tmp_import_prices) ";
        $this->_logMessage("Sql bnp_trafficlight: " . $sql . "\n" );
        $connW->query($sql); */

        $sql = "INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_bnp_adcodes, store_id, entity_id, bnp_adcodes FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id,0) <> 0 ON DUPLICATE KEY UPDATE value = b.bnp_adcodes";
        $this->_logMessage("Sql bnp_trafficlight: " . $sql . "\n" );
        $connW->query($sql);
		
		##################################
		        
        //update price for configurable
        #$sql = "UPDATE catalog_product_entity_decimal e SET value = (SELECT min(price) FROM bonaparte_tmp_import_prices WHERE entity_id_c = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_price, store_id, entity_id_c FROM bonaparte_tmp_import_prices)";
        $sql = "INSERT INTO catalog_product_entity_decimal (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_price, store_id, entity_id_c, price FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL ON DUPLICATE KEY UPDATE value = b.price";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);  
        
        
		######### update special_price for configurable products        #######
		######### should delete also the special prices that are equal  #######
		
        #$sql = "UPDATE catalog_product_entity_decimal e SET value = (SELECT min(special_price) FROM bonaparte_tmp_import_prices WHERE entity_id_c = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_special_price, store_id, entity_id_c FROM bonaparte_tmp_import_prices)";
        $sql = "INSERT INTO catalog_product_entity_decimal (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_special_price, store_id, entity_id_c, special_price FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL ON DUPLICATE KEY UPDATE value = b.special_price";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);    
        
        $sql = "DELETE FROM catalog_product_entity_decimal WHERE entity_type_id = $entityTypeId AND attribute_id = $attr_id_special_price AND (store_id, entity_id) IN (SELECT store_id, entity_id_c FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL AND price = special_price)";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);    
        
        
		//update special_from_date for configurable products
        #$sql = "UPDATE catalog_product_entity_datetime e SET value = (SELECT DISTINCT special_from_date FROM bonaparte_tmp_import_prices WHERE entity_id_c = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_special_from, store_id, entity_id_c FROM bonaparte_tmp_import_prices)";
        $sql = "INSERT INTO catalog_product_entity_datetime (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_special_from, store_id, entity_id_c, special_from_date FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL AND price <> special_price ON DUPLICATE KEY UPDATE value = b.special_from_date";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);    
        
        $sql = "DELETE FROM catalog_product_entity_datetime WHERE entity_type_id = $entityTypeId AND attribute_id = $attr_id_special_from AND (store_id, entity_id) IN (SELECT store_id, entity_id_c FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL AND price = special_price)";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);    
        
		//update special_to_date for configurable products
        #$sql = "UPDATE catalog_product_entity_datetime e SET value = (SELECT DISTINCT special_to_date FROM bonaparte_tmp_import_prices WHERE entity_id_c = e.entity_id AND store_id = e.store_id) WHERE (attribute_id, store_id, entity_id) IN (SELECT $attr_id_special_to, store_id, entity_id_c FROM bonaparte_tmp_import_prices)";
        $sql = "INSERT INTO catalog_product_entity_datetime (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_special_to, store_id, entity_id_c, special_from_date FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL AND price <> special_price ON DUPLICATE KEY UPDATE value = b.special_to_date";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql); 
		
		$sql = "DELETE FROM catalog_product_entity_datetime WHERE entity_type_id = $entityTypeId AND attribute_id = $attr_id_special_to AND (store_id, entity_id) IN (SELECT store_id, entity_id_c FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c IS NOT NULL AND price = special_price)";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);


        //update bnp_trafficlight
        $sql = "INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_traffic_light, store_id, entity_id_c, bnp_trafficlight FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id_c,0) <> 0 ON DUPLICATE KEY UPDATE value = b.bnp_trafficlight";
        $this->_logMessage("Sql bnp_trafficlight: " . $sql . "\n" );
        $connW->query($sql);

        //update bnp_pricecatalogue
        $sql = "INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_pricecat, store_id, entity_id_c, bnp_pricecat FROM bonaparte_tmp_import_prices b WHERE IFNULL(b.entity_id_c,0) <> 0 ON DUPLICATE KEY UPDATE value = b.bnp_pricecat";
        $this->_logMessage("Sql bnp_pricecat: " . $sql . "\n" );
        $connW->query($sql);


		#################################

		//activate products
		#$sql = "INSERT INTO catalog_product_entity_int (entity_type_id, attribute_id, store_id, entity_id, value) SELECT $entityTypeId, $attr_id_status, store_id, entity_id, 1 FROM bonaparte_tmp_import_prices ON DUPLICATE KEY UPDATE value = 1";
		$sql = "UPDATE catalog_product_entity_int e SET value = 1 WHERE attribute_id = $attr_id_status AND store_id = 0 AND  entity_id IN (SELECT entity_id_c FROM bonaparte_tmp_import_prices) AND EXISTS (SELECT 1 FROM catalog_product_entity e1 WHERE e1.entity_id = e.entity_id AND SUBSTR(e1.sku,6,1) = 'c')";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);
        
		//deactivate products
        $sql = "UPDATE catalog_product_entity_int e SET value = 1 WHERE attribute_id = $attr_id_status";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);

		$sql = "UPDATE catalog_product_entity_int e SET value = 2 WHERE attribute_id = $attr_id_status AND store_id = 0 AND NOT EXISTS (SELECT 1 FROM bonaparte_tmp_import_prices b WHERE b.entity_id_c = e.entity_id) AND EXISTS (SELECT 1 FROM catalog_product_entity e1 WHERE e1.entity_id = e.entity_id AND SUBSTR(e1.sku,6,1) = 'c')";
        $this->_logMessage($sql . "\n" );
        $connW->query($sql);
		
		//deactivate products for global
		#$sql = "UPDATE catalog_product_entity_int e SET value = 2 WHERE (attribute_id, store_id, entity_id) NOT IN (SELECT $attr_id_status, store_id, entity_id FROM bonaparte_tmp_import_prices)";
        #$this->_logMessage($sql . "\n" );
        #$connW->query($sql);
		
        $this->_logMessage('Finished importing prices' . "\n" );
    }

}
