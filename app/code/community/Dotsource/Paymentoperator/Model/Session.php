<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Session
    extends Mage_Core_Model_Session_Abstract
{

    /** Holds the paymentoperator transaction model */
    protected $_transactionModel = null;


    public function __construct()
    {
        $this->init('paymentoperator');
    }


    /**
     * Clear the paymentoperator transaction model.
     */
    public function clearSession()
    {
        $this->_transactionModel = null;
        $this->unsTransactionModelId();
    }


    /**
     * Return a new paymentoperator transaction model or an already used
     * transaction model in the current checkout session.
     *
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function getTransactionModel()
    {
        //Already restored?
        if (empty($this->_transactionModel)) {
            //Get the paymentoperator transaction model id from the current session
            $modelId = $this->getTransactionModelId();

            /* @var $transactionModel Dotsource_Paymentoperator_Model_Transaction */
            $transactionModel   = null;

            //Load the old one or create a new one
            if (!empty($modelId)) {
                //Restore the transaction model
                $transactionModel = Mage::getModel('paymentoperator/transaction')->load($modelId);

                //Validate the transaction model
                if (empty($transactionModel) || !$transactionModel->hasId()) {
                    $this->clearSession();

//                    if ($this->_getHelper()->isDemoMode()) {
//                        Mage::throwException("Can't restore old transaction model from session. Please try again.");
//                    } else {
                        return $this->getTransactionModel();
//                    }
                }
            } else {
                $transactionModel = Mage::getModel('paymentoperator/transaction');
            }

            //Set the transaction model id for new created model
            if (empty($modelId) && $transactionModel->hasId()) {
                $this->setTransactionModelId($transactionModel->getId());
            }

            //Store the transaction model in the session
            $this->_transactionModel = $transactionModel;
        }

        return $this->_transactionModel;
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