<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Transaction
    extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('paymentoperator/transaction');
    }


    /**
     * @see Varien_Object::hasDataChanges()
     * Need to fix magento bug:
     * http://www.magentocommerce.com/bug-tracking/issue?issue=9506
     *
     * @return bool
     */
    public function hasDataChanges()
    {
        return true;
    }


    /**
     * Return the reference code and save the model if need.
     *
     * @return string
     */
    public function getTransactionCode()
    {
        //Check if the current model is already saved
        if (!$this->hasId()) {
            $this->save();
        }

        //The transaction code as zero filled id
        return $this->_getHelper()->getConverter()->formatAsTransactionCode(
            $this->getId()
        );
    }


    /**
     * Return the id as int.
     *
     * @return int
     */
    public function getId()
    {
        //Get the current id
        $id = parent::getId();

        //If we have an id cast to int
        if (null !== $id) {
            $id = (int)$id;
        }

        return $id;
    }


    /**
     * Check if the current model has an id.
     *
     * @return boolean
     */
    public function hasId()
    {
        $id = $this->getIdFieldName();

        //check if field id name is setup
        if (empty($id)) {
            return $this->hasData('id');
        } else {
            return $this->hasData($id);
        }
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}