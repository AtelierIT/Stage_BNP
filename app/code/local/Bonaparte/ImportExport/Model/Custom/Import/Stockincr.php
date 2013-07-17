<?php

/**
 * Stores the business logic for the custom attribute import
 *
 * @category    Bonaparte
 * @package     Bonaparte_ImportExport
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_ImportExport_Model_Custom_Import_Stockincr extends Bonaparte_ImportExport_Model_Custom_Import_AbstractCsv
{
	private $_bnpAttributes = array();
    /**
     * Path at which the category configuration is found
     *
     * @var string
     */
    //const CONFIGURATION_FILE_PATH = '/dump_files/csv/Pulsen_incremental_inventory.TXT';
    const CONFIGURATION_FILE_PATH = '/dump_files/WBN2435';
    const CONFIGURATION_FILE_PATH_TMP = '/dump_files/bonaparte_tmp_import_stock.csv';

    /**
     * Construct import model
     */
    public function _construct()
    {
        $this->_source = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH;
        $this->_init();

        
    }
	
	public function _getAttributeLabelId($attributeCode,$label)
    {
        if (isset($this->_bnpAttributes[$attributeCode])){
            return $this->_bnpAttributes[$attributeCode][$label];
        }

        $productModel = Mage::getModel('catalog/product');
        $attributeBnpCatalogue = $productModel->getResource()->getAttribute($attributeCode);

        foreach ($attributeBnpCatalogue->getSource()->getAllOptions() as $option){
            $this->_bnpAttributes[$attributeCode][$option['label']] = $option['value'];
        }

        return $this->_bnpAttributes[$attributeCode][$label];

    }

    /**
     * Specific category functionality
     */
    public function start($options = array())
    {
		$csv_data = array();
        $csv_data = &$this->_data;
        
        $product = Mage::getModel('catalog/product');
        
        //remove duplicates and create sku from info
        $arr_sku_stock = array();
        while ($this->next() == true) {
			$item = $this->_currentRow;
			$sku = "";
            if ($item[1]){
			$sku = $item[0]."-".$item[1];
            }else{$sku = $item[0]; };
			switch ($item[7]) {
				case "0000001": $traffic_light = "1";
				case "9999999": $traffic_light = "3";
				default: $traffic_light = "2";
			}
			
			#if (array_key_exists($sku,$arr_sku_stock))
			$arr_sku_stock[$sku] = array("qty" => $item[2], "is_in_stock" => ($item[2]>0)?1:0, "traffic_light" => $traffic_light);
		}

        //tmunteanu v2 -->start
        //add date to csv file
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connW = Mage::getSingleton('core/resource')->getConnection('core_write');

        $config  = Mage::getConfig()->getResourceConnectionConfig("default_setup");

        $attr_id_traffic_light = $eavAttribute->getIdByCode('catalog_product', 'bnp_trafficlight');
        $baseDir = Mage::getBaseDir();

        $sql = "TRUNCATE bonaparte_tmp_import_stock;";
        $connW->query($sql);

        $fileHandler2 = fopen($baseDir . self::CONFIGURATION_FILE_PATH_TMP, 'w'); //tmunteanu
        //tmunteanu v2 <--end
		
		//print_r($arr_sku_stock);
		
		//return;

        #echo count($arr_sku_stock);

        foreach ($arr_sku_stock as $key => $item) {
			 if (!$key) continue;

            //tmunteanu v2 start
            fputcsv($fileHandler2,array($key, $item['qty'], $item['traffic_light'],0,$item['is_in_stock']));

            //tmunteanu v2 end

            /*
			 $productId = $product->getIdBySku($key);
			 if ($productId) {
				$product->load($productId);
				//set traffic light
				$product->setBnpTrafficlight($this->_getAttributeLabelId('bnp_trafficlight',$item['traffic_light']));
				// get product's stock data such quantity, in_stock etc
				//$stockData = $product->getStockData();
		 
				// update stock data using new data
				//$stockData['qty'] = $item[1]; //second column from csv file
				//$stockData['is_in_stock'] = 1;
			 
				// then set product's stock data to update
				$product->setStockData($item);
				try {
					$product->save();
					echo "Product stock updated for sku " . $key . "\n";
				}
				catch (Exception $ex) {
					echo $ex->getMessage();
				}
			 }
            */
			
		}

        fclose($fileHandler2);


        if( ($fp = popen("mysql -u ".$config->username." -p".$config->password." ".$config->dbname." -e \"LOAD DATA LOCAL INFILE '$baseDir/dump_files/bonaparte_tmp_import_stock.csv' INTO TABLE bonaparte_tmp_import_stock FIELDS TERMINATED BY ',';\";", "r")) ) {
            echo "Done insert temp \n";
            while( !feof($fp) ){
                echo fread($fp, 1024);
                flush(); // you have to flush buffer
            }
            fclose($fp);
        }

        //update entity_id
        $sql = "UPDATE bonaparte_tmp_import_stock bt SET entity_id = (SELECT entity_id FROM catalog_product_entity WHERE sku = bt.sku)";
        $connW->query($sql);

        //update entity id for uk sku (sizes)
        $sql = "UPDATE bonaparte_tmp_import_stock bt SET entity_id = (SELECT DISTINCT simple_entity_id FROM bonaparte_styles bs WHERE uk_sku = bt.sku AND uk_sku <> '') WHERE IFNULL(entity_id,0) = 0 AND EXISTS (SELECT 1 FROM bonaparte_styles bs2 WHERE bs2.uk_sku <> '' AND bs2.uk_sku = bt.sku )";
        $connW->query($sql);

        //insert + update bonaparte_trafficlight
        $sql = "UPDATE catalog_product_entity_int e SET value = (SELECT traffic_light FROM bonaparte_tmp_import_stock b WHERE b.entity_id = e.entity_id) WHERE attribute_id = $attr_id_traffic_light AND EXISTS (SELECT 1 FROM bonaparte_tmp_import_stock b2 WHERE IFNULL(b2.entity_id,0) <> 0 AND b2.entity_id = e.entity_id)  ";
        $connW->query($sql);

        //update stock
        $sql = "UPDATE cataloginventory_stock_item e SET e.qty = (SELECT b.qty FROM bonaparte_tmp_import_stock b WHERE b.entity_id = e.product_id), e.is_in_stock = (SELECT b.is_in_stock FROM bonaparte_tmp_import_stock b WHERE b.entity_id = e.product_id) WHERE EXISTS (SELECT 1 FROM bonaparte_tmp_import_stock b2 WHERE b2.entity_id = e.product_id) ";
        $connW->query($sql);

        $sql = "UPDATE cataloginventory_stock_status e SET e.qty = (SELECT b.qty FROM bonaparte_tmp_import_stock b WHERE b.entity_id = e.product_id), e.stock_status = (SELECT b.is_in_stock FROM bonaparte_tmp_import_stock b WHERE b.entity_id = e.product_id) WHERE EXISTS (SELECT 1 FROM bonaparte_tmp_import_stock b2 WHERE b2.entity_id = e.product_id) ";
        $connW->query($sql);


        echo 'DONE \n';
    }

}
