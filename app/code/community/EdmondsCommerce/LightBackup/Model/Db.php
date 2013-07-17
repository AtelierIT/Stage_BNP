<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * It is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 *
 * @category   EdmondsCommerce
 * @package    EdmondsCommerce_LightBackup
 * @copyright  Copyright (c) 2010 Edmonds Commerce (http://www.edmondscommerce.co.uk)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Database backup model
 *
 * @category    Mage
 * @package     Mage_Backup
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class EdmondsCommerce_LightBackup_Model_Db extends Mage_Backup_Model_Db
{

    /**
     * Tables that we do not want the data for as they make the backup files too big
     */
    protected $_logTables = array(
        'dataflow_batch_export',
        'dataflow_batch_import',
        'log_customer',
        'log_quote',
        'log_summary',
        'log_summary_type',
        'log_url',
        'log_url_info',
        'log_visitor',
        'log_visitor_info',
        'log_visitor_online',
        'report_event'
    );

    protected $_logTablesWithPrefix=array();

    /**
     * Get an array of log tables (etc) with the prefix
     * @return array
     */
    protected function getLogTables(){
        if(empty($this->_logTablesWithPrefix)){
            $R=Mage::getSingleton('core/resource');
            foreach($this->_logTables as $t){
                $this->_logTablesWithPrefix[] = $R->getTableName($t);
            }
        }
        return $this->_logTablesWithPrefix;
    }

    protected function isLogTable($t){
        $logTables=$this->getLogTables();
        return (in_array($t, $logTables));
    }


    /**
     * Create backup and stream write to adapter
     *
     * @param Mage_Backup_Model_Backup $backup
     * @return Mage_Backup_Model_Db
     */
    public function createBackup(Mage_Backup_Model_Backup $backup)
    {
        
        $backup->open(true);

        $this->getResource()->beginTransaction();

        $tables = $this->getResource()->getTables();

        $backup->write($this->getResource()->getHeader());

        foreach ($tables as $table) {
            $backup->write($this->getResource()->getTableHeader($table) . $this->getResource()->getTableDropSql($table) . "\n");
            $backup->write($this->getResource()->getTableCreateSql($table, false) . "\n");

            $tableStatus = $this->getResource()->getTableStatus($table);

            if ($tableStatus->getRows() && !$this->isLogTable($table)) { //only write data for tables that are not log tables (etc)
                $backup->write($this->getResource()->getTableDataBeforeSql($table));

                if ($tableStatus->getDataLength() > self::BUFFER_LENGTH) {
                    if ($tableStatus->getAvgRowLength() < self::BUFFER_LENGTH) {
                        $limit = floor(self::BUFFER_LENGTH / $tableStatus->getAvgRowLength());
                        $multiRowsLength = ceil($tableStatus->getRows() / $limit);
                    }
                    else {
                        $limit = 1;
                        $multiRowsLength = $tableStatus->getRows();
                    }
                }
                else {
                    $limit = $tableStatus->getRows();
                    $multiRowsLength = 1;
                }

                for ($i = 0; $i < $multiRowsLength; $i ++) {
                    $backup->write($this->getResource()->getTableDataSql($table, $limit, $i*$limit));
                }

                $backup->write($this->getResource()->getTableDataAfterSql($table));
            }
        }
        $backup->write($this->getResource()->getTableForeignKeysSql());
        $backup->write($this->getResource()->getFooter());

        $this->getResource()->commitTransaction();

        $backup->close();

        return $this;
    }

}
