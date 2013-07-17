<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Handler_Session
    extends Dotsource_Paymentoperator_Model_Error_Handler_Abstract
{

    public function _construct()
    {
        parent::_construct();

        //Add default callback for error
        if (!$this->hasCallback()) {
            $this->setCallback('addError');
        }
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Error_Handler_Interface::processHandler()
     *
     * @param Varien_Object $request
     */
    public function processHandler(Varien_Object $request)
    {
        //Get the message
        $message    = $request->getMessage();

        //If the message is empty we no need to add the message
        if (empty($message)) {
            return;
        }

        //Get the callback settings
        $session    = $this->getSession();
        $callback   = $this->getCallback();

        //Check for configured session
        if ($session && $callback) {
            //Check for model path
            if (is_string($session)) {
                $session = Mage::getSingleton($session);
            } elseif (!$session instanceof Mage_Core_Model_Session_Abstract) {
                $this->_getManager()->setError(
                    'Session need to extends from the class Mage_Core_Model_Session_Abstract.'
                );
                return;
            }

            //Check the callability
            if (!is_callable(array($session, $callback))) {
                $this->_getManager()->setError('The callback method can\'t call for the current session.');
                return;
            }

            //Add the message to the session
            $session->$callback($message);
        } else {
            $this->_getManager()->setError('Session and callback are not configured.');
            return;
        }
    }
}