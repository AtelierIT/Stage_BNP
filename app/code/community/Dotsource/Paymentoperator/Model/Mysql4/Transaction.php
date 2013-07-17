<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Mysql4_Transaction
    extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {
        $this->_init('paymentoperator/transaction', 'entity_id');
    }


    /**
     * Check if the given reference code is unique.
     *
     * @param string $reference
     * @return boolean
     */
    public function isTransactionCodeUnique($reference)
    {
        //Empty is bad
        if (empty($reference)) {
            return false;
        }

        //Try to select the reference code
        $select = $this->_getReadAdapter()
            ->select()
            ->from($this->getMainTable())
            ->where('transaction_code = ?', $reference);

        //Execute the query
        $result = $this->_getReadAdapter()->fetchOne($select);

        //If the row count is zero the reference code is not in the table
        return empty($result);
    }
}