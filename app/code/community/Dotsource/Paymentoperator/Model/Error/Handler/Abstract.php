<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Error_Handler_Abstract
    extends Varien_Object
{

    /** Holds the manager */
    protected $_manager = null;


    /**
     * Process the request.
     *
     * @param Varien_Object $request
     */
    public abstract function processHandler(Varien_Object $request);


    /**
     * Set the manager to the handler..
     *
     * @param Dotsource_Paymentoperator_Model_Error_Manager $manager
     */
    public function addManager(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        $this->_manager = $manager;
    }


    /**
     * Return the manager from the handler.
     *
     * @return Dotsource_Paymentoperator_Model_Error_Manager
     */
    protected function _getManager()
    {
        return $this->_manager;
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