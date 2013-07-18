<?php ini_set("display_errors",1);


/**
 * Stores the business logic for the custom Check Imports
 *
 * @category    Bonaparte
 * @package     Bonaparte_ImportExport
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_ImportExport_Model_Custom_Import_Checkimport1 extends Bonaparte_ImportExport_Model_Custom_Import_Abstract
{
    /**
     * Path at which the category configuration is found
     *
     * @var string
     */


    /**
     * The magento code for attibute adcodes1
     *
     * @var string
     */

    /**
     * Store current ad code for the sku
     *
     * @var array
     */

    /**
     * Construct import model
     */
    const CONFIGURATION_FILE_PATH = '/dump_files/cino_files.csv';


    public function _construct()
    {
        $this->_configurationFilePath = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH;

        //create the array with CINO as key and file as value
        $fileHandler = fopen($this->_configurationFilePath, 'r');

        while ($row = fgetcsv($fileHandler)) {

            $this->_data[$row[1]] = $row[0];

        }


        fclose($fileHandler);
    }

    /**
     * Set relation between configurable products
     */
    /**
     * Specific category functionality
     */
    public function start($options = array())
    {
        $this->_logMessage('Started checking import 1' . "\n" );


        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connW = Mage::getSingleton('core/resource')->getConnection('core_write');

        $config  = Mage::getConfig()->getResourceConnectionConfig("default_setup");

        #insert relation
        $sql ="SELECT * FROM bonaparte_tmp_import_prices WHERE entity_id IS NULL";
        $results = $conn->fetchAll($sql);
		
		//open files for results
		$fileHandler2 = fopen("checkimport1_uk_exists.csv","w");
		$fileHandler3 = fopen("checkimport1_other_exists.csv","w");
		$fileHandler4 = fopen("checkimport1_non_exists.csv","w");

        $arr_cino_uk = array();
        $arr_cino_other = array();
        $arr_cino_non = array();

        #print_r($results);
		#print_r($this->_data);
		
		foreach ($results as $row ) {
			//get cino
			$arr_sku = explode('-',$row['sku']);
			$cino = $arr_sku[0];
			
			if (isset($this->_data[$cino])) {
				#echo $cino . " <--> " . $row['sku'] . " <--> " . $row['store_id'] . " exists in product file \n";
				if ($row['store_id'] == 7) {
                    fputcsv($fileHandler2, array($cino, $row['sku'], $row['store_id']));
                    array_push($arr_cino_uk,(string)$cino);
                }
				else {
                    fputcsv($fileHandler3, array($cino, $row['sku'], $row['store_id']));
                    array_push($arr_cino_other,(string)$cino);
                }
			}
			else {
				#echo $cino . " <--> " . $row['sku']. " <--> " . $row['store_id'] . " NOT EXISTS in product file \n";
				fputcsv($fileHandler4, array($cino, $row['sku'], $row['store_id']));
                array_push($arr_cino_non,(string)$cino);
			}
		}
		
		fclose($fileHandler2);
		fclose($fileHandler3);
		fclose($fileHandler4);

         echo $this->_data['10282'];

        $arr_cino_uk    = array_unique($arr_cino_uk, SORT_STRING);
        $arr_cino_other = array_unique($arr_cino_other, SORT_STRING);
        $arr_cino_non   = array_unique($arr_cino_non, SORT_STRING);

        $fileHandler2 = fopen("cino_uk_exists.csv","w");
        $fileHandler3 = fopen("cino_other_exists.csv","w");
        $fileHandler4 = fopen("cino_non_exists.csv","w");

        fputcsv($fileHandler2,array("cino", "file"));
        fputcsv($fileHandler3,array("cino", "file"));
        fputcsv($fileHandler4,array("cino", "file"));

        foreach ($arr_cino_uk as $cino) {
            fputcsv($fileHandler2,array($cino, $this->_data[$cino]));
        }

        foreach ($arr_cino_other as $cino) {
            fputcsv($fileHandler3,array($cino, $this->_data[$cino]));
        }

        foreach ($arr_cino_non as $cino) {
            fputcsv($fileHandler4,array($cino));
        }


        fclose($fileHandler2);
        fclose($fileHandler3);
        fclose($fileHandler4);

        $this->_logMessage('Stop checking import 1' );
    }

}


?>