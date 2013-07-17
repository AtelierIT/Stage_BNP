<?php

abstract class Bonaparte_ImportExport_Model_Custom_Import_AbstractCsv extends Mage_ImportExport_Model_Abstract {
    /**
     * Field delimiter.
     *
     * @var string
     */
    protected $_delimiter = ';';

    /**
     * Field enclosure character.
     *
     * @var string
     */
    protected $_enclosure = '"';

    /**
     * Source file handler.
     *
     * @var resource
     */
    protected $_fileHandler;

    
    
    /**
     * Stores the CSV configuration file used
     *
     * @var Varien_Simplexml_Config
     */
    private $_config = null;

    /**
     * Store data that will be used in the import
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Stores the path to the XML configuration file
     *
     * @var mixed string|array
     */
    protected $_source = '';

    /**
     * Object destructor.
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_resource($this->_fileHandler)) {
            fclose($this->_fileHandler);
        }
    }
    
    /**
     * Method called as last step of object instance creation. Can be overrided in child classes.
     *
     * @return Mage_ImportExport_Model_Import_Adapter_Abstract
     */
    protected function _init()
    {
		try {
			$this->_fileHandler = fopen($this->_source, 'r');
		} catch (Exception $e)
		{
			echo "Error on reading file";
			return;
		}
		
		$this->_currentKey = 0;
		//$this->parseCsv();
		
        return $this;
    }
    
    
    public function next()
    {
        $this->_currentRow = fgetcsv($this->_fileHandler, null, $this->_delimiter, $this->_enclosure);
        $this->_currentKey = $this->_currentRow ? $this->_currentKey + 1 : null;
        
        if ($this->_currentRow) return true; else return false;
    }
    
    /**
     * Method called to get data from csv file into array
     */
    
    protected function parseCsv() {
		
		$csv_data = array();
		while ($this->_currentRow = fgetcsv($this->_fileHandler, null, $this->_delimiter, $this->_enclosure)) {
			$csv_data[] = $this->_currentRow;
		}
		
		$this->_data = $csv_data;
	} 

    /**
     * Starts the import process
     *
     * @return mixed
     */
    abstract function start();
}
