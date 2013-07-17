<?php

require_once 'abstract.php';

/**
 * Bonaparte import shell script
 *
 * @category    Bonaparte
 * @package     Bonaparte_Shell
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_Shell_Import extends Mage_Shell_Abstract
{
    const IMPORT_TYPE_ATTRIBUTES = 'attributes';
    const IMPORT_TYPE_CATEGORIES = 'categories';
    const IMPORT_TYPE_PRODUCTS = 'products';
    const IMPORT_TYPE_PRICES = 'prices';
    const IMPORT_TYPE_STOCK = 'stock';
    const IMPORT_TYPE_STOCKINCR = 'stockincr';


    public function run() {
        switch($this->_args['type']) {
            case self::IMPORT_TYPE_ATTRIBUTES:
                Mage::getModel('Bonaparte_ImportExport/Custom_Import_Attributes')->start($this->_args);
                break;
            case self::IMPORT_TYPE_CATEGORIES:
                Mage::getModel('Bonaparte_ImportExport/Custom_Import_Categories')->start($this->_args);
                break;
            case self::IMPORT_TYPE_PRODUCTS:
                Mage::getModel('Bonaparte_ImportExport/Custom_Import_Products')->start($this->_args);
                break;
            case self::IMPORT_TYPE_PRICES:
                Mage::getModel('Bonaparte_ImportExport/Custom_Import_Prices')->start($this->_args);
                break;
            case self::IMPORT_TYPE_STOCK:
                Mage::getModel('Bonaparte_ImportExport/Custom_Import_Stock')->start($this->_args);
                break;
            case self::IMPORT_TYPE_STOCKINCR:
                Mage::getModel('Bonaparte_ImportExport/Custom_Import_Stockincr')->start($this->_args);
                break;
            default:
                echo $this->usageHelp();
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f bonaparte_import.php -- [options]

  --type <type>                                         attributes|categories|products|prices
  --remove_attributes_with_identical_attribute_code     only for attributes

USAGE;
    }

}

$shell = new Bonaparte_Shell_Import();
$shell->run();