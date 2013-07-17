<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Handler_Callback
    extends Dotsource_Paymentoperator_Model_Error_Handler_Abstract
{

    /**
     * Init some variables.
     */
    protected function _construct()
    {
        parent::_construct();

        if (!$this->hasUseCallback()) {
            $this->setUseCallback(true);
        }
    }

    /**
     * Process a callback.
     *
     * @param $request
     */
    public function processHandler(Varien_Object $request)
    {
        //Is use callbacks active and we have a callback?
        if (!$this->getUseCallback() || !$this->getCallback()) {
            return;
        }

        //Get the callback
        $callbackValue  = $this->getCallback();
        $callbacks      = array();

        //Format the callback to the right format
        if (is_string($callbackValue)) {
            try {
                //Explode the callback
                $callbackParts  = explode(' ', $callbackValue);

                //Get all callbacks
                foreach ($callbackParts as $callbackPart) {
                    $callbacks[] = $this->_parseCallbackfromString($callbackPart);
                }
            } catch (Exception $e) {
            }
        } else {
            $callbacks = array($callbackValue);
        }

        //Process the callback
        try {
            foreach ($callbacks as $callback) {
                //Check if we have something to call
                if (!is_array($callback) && $callback) {
                    $this->_getManager()->setError(
                        'Only a string of the format "model::method" or an callback array are valid callbacks.'
                    );
                } else {
                    call_user_func_array($callback, array($this->_getManager()));
                }
            }
        } catch(Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Return a callback array from the given string. The string should
     * have the format: model::method
     *
     * @param   string  $callback
     * @return  array
     */
    protected function _parseCallbackfromString($callback)
    {
        $run = array();
        if (!preg_match(Mage_Cron_Model_Observer::REGEX_RUN_MODEL, $callback, $run)) {
            Mage::throwException(Mage::helper('cron')->__('Invalid model/method definition, expecting "model/class::method".'));
        }

        if (!($model = Mage::getModel($run[1])) || !method_exists($model, $run[2])) {
            Mage::throwException(Mage::helper('cron')->__('Invalid callback: %s::%s does not exist', $run[1], $run[2]));
        }

        //Create the callbacks
        return array($model, $run[2]);
    }
}