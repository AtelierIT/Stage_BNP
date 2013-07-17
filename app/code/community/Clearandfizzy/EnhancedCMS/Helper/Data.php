<?php
/**
 * @category    Clearandfizzy
 * @package     Clearandfizzy_EnhancedCMS
 * @copyright   Copyright (c) 2012 Clearandfizzy ltd. (http://www.clearandfizzy.com/)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */
class Clearandfizzy_EnhancedCMS_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getImportPath() {
		$path = Mage::getBaseDir('var') . DS . 'import' . DS . 'enhancedcms' . DS;

		if (is_dir_writeable($path) != true) {
			mkdir ($path, '0744', $recursive  = true );
		} // end

		return $path;
	}

}