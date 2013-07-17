<?php
/**
 * @category    Clearandfizzy
 * @package     Clearandfizzy_EnhancedCMS
 * @copyright   Copyright (c) 2012 Clearandfizzy ltd. (http://www.clearandfizzy.com/)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */
class Clearandfizzy_EnhancedCMS_Model_Import_Page_Csv extends Clearandfizzy_EnhancedCMS_Model_Import_Abstract_Csv {

	private $array_delimiter = ';';
	private $delimiter = ',';
	private $header_columns;

	protected $_modelname = 'cms/page';

}
