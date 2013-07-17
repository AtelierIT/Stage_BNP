<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Manager
{

    /** Holds the chain elements */
    protected $_handler         = array();

    /** Holds all handlers in a flat structure */
    protected $_flatHandlers    = array();

    /** Holds a error */
    protected $_error           = null;

    /** Holds the request */
    protected $_request         = null;


    /**
     * Add a new handler with the given priority.
     * Lower priority will process first.
     *
     * @param mixed     $handler
     * @param string    $alias
     * @param array     $args
     * @param int       $priority
     * @return Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function addHandler($handler, $alias, array $args = array(), $priority = 100)
    {
        //The alias can only be used unique
        if (isset($this->_flatHandlers[$alias])) {
            throw new Exception("The alias \"$alias\" is already in use.");
        }

        //Get the object
        if (is_string($handler)) {
            $handler = Mage::getModel('paymentoperator/error_handler_'.$handler, $args);
        }

        //Check the object
        if (!$handler instanceof Dotsource_Paymentoperator_Model_Error_Handler_Abstract) {
            throw new Exception("Handler need to extends from class Dotsource_Paymentoperator_Model_Error_Handler_Abstract.");
        }

        //Create a new bucket for the priority
        if (!isset($this->_handler[$priority])) {
            $this->_handler[$priority] = array();
        }

        //Add the handler
        $this->_handler[$priority][$alias]  = $handler;
        $this->_flatHandlers[$alias]        = $handler;
        $handler->addManager($this);

        return $this;
    }

    /**
     * Return a given handler from the given alias name.
     *
     * @param string $alias
     * @return Dotsource_Paymentoperator_Model_Error_Handler_Abstract|null
     */
    protected function _getHandler($alias)
    {
        //Check if we have an handler
        if (isset($this->_flatHandlers[$alias])) {
            return $this->_flatHandlers[$alias];
        }

        return null;
    }

    /**
     * Remove the handler with the given alias.
     *
     * @param   string  $alias
     * @return  Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function removeHandler($alias)
    {
        if (isset($this->_flatHandlers[$alias])) {
            foreach ($this->_getHandlers() as $priority => $handlers) {
                if (isset($handlers[$alias])) {
                    unset($this->_handler[$priority][$alias]);
                    break;
                }
            }

            //Remove from flat
            unset($this->_flatHandlers[$alias]);
        }

        return $this;
    }

    /**
     * Update the handler.
     *
     * @return Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function updateHandler($alias, $method, $args = array())
    {
        //Get the handler and call the method
        $handler = $this->_getHandler($alias);
        if ($handler) {
            if (!is_array($args)) {
                $args = array($args);
            }

            call_user_func_array(array($handler, $method), $args);
        }

        return $this;
    }

    /**
     * Return true if the alias is available.
     *
     * @param   string  $alias
     * @return  boolean
     */
    public function hasHandler($alias)
    {
        return null !== $this->_getHandler($alias);
    }

    /**
     * Process the given request object.
     *
     * @param Varien_Object $request
     */
    public function processChain(Varien_Object $request)
    {
        //Set the request as current processing request
        $this->_request = $request;

        //Sort the handlers
        ksort($this->_handler);

        /* @var $handler Dotsource_Paymentoperator_Model_Error_Handler_Abstract */
        foreach ($this->_getHandlers() as $handlers) {
            foreach ($handlers as $handler) {
                //Process the handler
                $handler->processHandler($request);

                //Check for error
                if ($this->hasError()) {
                    Mage::log($this->getError(), Zend_Log::ERR, 'paymentoperator_error_manager.log', true);
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Process the given message with the given arguments.
     *
     * @param   string  $message
     * @param   array   $messageAguments
     * @return  Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function processMessage($message, array $messageAguments = array())
    {
        return $this->processChain(
            new Varien_Object(
                array(
                    'message'       => $message,
                    'arguments'     => $messageAguments
                )
            )
        );
    }

    /**
     * Process the given error code.
     *
     * @param mixed     $errorCode
     * @param string    $messageSection
     * @param string    $sectionArea
     * @param array     $messageAguments
     * @return Dotsource_Clickpay_Model_Error_Manager
     */
    public function processErrorCode(
        $errorCode,
        $messageSection         = null,
        $sectionArea            = null,
        array $messageAguments  = array()
    )
    {
        //Try to parse the error code from the given model
        $response = null;
        if (is_object($errorCode)) {
            //Request to response
            if ($errorCode instanceof Dotsource_Paymentoperator_Model_Payment_Request_Request) {
                $errorCode  = $errorCode->getResponseModel();
            }

            //Response to error code
            if ($errorCode instanceof Dotsource_Paymentoperator_Model_Payment_Response_Response) {
                $response   = $errorCode;
                $errorCode  = $errorCode->getResponse()->getCode();
            } else {
                throw new Exception("The given object ". get_class($errorCode) . " can handle.");
            }
        }

        return $this->processChain(
            new Varien_Object(
                array(
                    'message'           => $errorCode,
                    'message_section'   => $messageSection,
                    'section_area'      => $sectionArea,
                    'arguments'         => $messageAguments,
                    'is_error_code'     => true,
                    'paymentoperator_response' => $response,
                )
            )
        );
    }

    /**
     * Return the current processing request.
     *
     * @return Varien_Object
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Return the chain elements as array.
     *
     * @return array
     */
    protected function _getHandlers()
    {
        return $this->_handler;
    }

    /**
     * Set a error message to the manager.
     *
     * @param string $message
     * @return Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function setError($message)
    {
        $this->_error = $message;
        return $this;
    }

    /**
     * Return the error.
     *
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Check if the manager has an error message.
     *
     * @return boolean
     */
    public function hasError()
    {
        return null !== $this->_error;
    }

    /**
     * Reset the error message.
     *
     * @return Dotsource_Paymentoperator_Model_Error_Manager
     */
    public function reset()
    {
        $this->_error   = null;
        $this->_request = null;
        return $this;
    }
}