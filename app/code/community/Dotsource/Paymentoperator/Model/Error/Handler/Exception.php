<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Handler_Exception
    extends Dotsource_Paymentoperator_Model_Error_Handler_Abstract
{

    /**
     * @see Dotsource_Paymentoperator_Model_Error_Handler_Interface::processHandler()
     *
     * @param Varien_Object $request
     */
    public function processHandler(Varien_Object $request)
    {
        $message    = $request->getMessage();
        $module     = $this->getModule();
        $code       = $this->getCode();

        //Throw the right exception
        if (!$module) {
            throw new Exception($message, $code);
        } else {
            //Throw the exception
            throw Mage::exception($module, $message, $code);
        }
    }
}