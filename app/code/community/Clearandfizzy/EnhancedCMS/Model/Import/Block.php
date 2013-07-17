<?php
/**
 * @category    Clearandfizzy
 * @package     Clearandfizzy_EnhancedCMS
 * @copyright   Copyright (c) 2012 Clearandfizzy ltd. (http://www.clearandfizzy.com/)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */
class Clearandfizzy_EnhancedCMS_Model_Import_Block extends Mage_Core_Model_Abstract {


	public function process($filepath) {

		// get the file extension
		$array = pathinfo($filepath);

		switch ($array["extension"] ) {

			case "csv":
			case "txt":
				Mage::getModel('clearandfizzy_enhancedcms/import_block_csv')->process($filepath);
			break;

			default:
				Mage::throwException("File is of unknown format, cannot process to import");
			break;
 		} // end

	} // end

}
