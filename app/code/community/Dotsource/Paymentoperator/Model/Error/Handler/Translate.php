<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Handler_Translate
    extends Dotsource_Paymentoperator_Model_Error_Handler_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        //Add default helper
        if (!$this->hasHelper()) {
            $this->setHelper('core');
        }

        //Add default args
        if (!$this->hasArguments()) {
            $this->setArguments(array());
        }
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Error_Handler_Interface::processHandler()
     *
     * @param Varien_Object $request
     */
    public function processHandler(Varien_Object $request)
    {
        $message    = $request->getMessage();
        $args       = $request->getArguments();
        $helper     = $this->getHelper();

        //Get helper from string
        if (is_string($helper)) {
            $helper = Mage::helper($helper);
        }

        //Check for helper
        if (!$helper instanceof Mage_Core_Helper_Abstract) {
            $this->_getManager()->setError(
                'Helper object is not an instance of Mage_Core_Helper_Abstract.'
            );
            return;
        }

        //We need an array
        if (!$args) {
            $args = array();
        }

        //Add to the args
        array_unshift($args, $message);

        //Set the translated massage
        $message = call_user_func_array(array($helper, '__'), $args);
        $request->setMessage($message);
    }
}