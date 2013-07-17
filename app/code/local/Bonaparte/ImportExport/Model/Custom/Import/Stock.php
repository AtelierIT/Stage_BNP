<?php

/**
 * Stores the business logic for the custom attribute import
 *
 * @category    Bonaparte
 * @package     Bonaparte_ImportExport
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_ImportExport_Model_Custom_Import_Stock extends Bonaparte_ImportExport_Model_Custom_Import_AbstractCsv
{
    /**
     * Path at which the category configuration is found
     *
     * @var string
     */
    //const CONFIGURATION_FILE_PATH = '/dump_files/csv/Pulsen_incremental_inventory.TXT';
    const CONFIGURATION_FILE_PATH = '/dump_files/csv/ARTIKLAR.TXT';

    /**
     * Construct import model
     */
    public function _construct()
    {
        $this->_source = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH;
        $this->_init();

        
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
			$sku = substr($item[3],0,5)."-".$item[6];
			
			#if (array_key_exists($sku,$arr_sku_stock))
			$arr_sku_stock[$sku] = array("qty" => $item[8], "is_in_stock" => ($item[8]>0)?1:0);
		}
		
		//print_r($arr_sku_stock);
		
		//return;
        
        foreach ($arr_sku_stock as $key => $item) {
			 if (!$key) continue;
			
			 $productId = $product->getIdBySku($key);
			 if ($productId) {
				$product->load($productId);
				
				// get product's stock data such quantity, in_stock etc
				//$stockData = $product->getStockData();
		 
				// update stock data using new data
				//$stockData['qty'] = $item[1]; //second column from csv file
				//$stockData['is_in_stock'] = 1;
			 
				// then set product's stock data to update
				$product->setStockData($item);
				try {
					$product->save();
					echo "Product stock updated for sku ".$key;
				}
				catch (Exception $ex) {
					echo $ex->getMessage();
				}
			 } 
			
		}

        echo 'DONE';
    }

}
