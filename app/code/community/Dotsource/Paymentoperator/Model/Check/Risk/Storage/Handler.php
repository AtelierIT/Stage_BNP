<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Check_Risk_Storage_Handler
{

    /** Holds all registered handlers */
    protected $_handler = array();


    /**
     * Add a handler to the collection.
     *
     * @param $handler
     * @return Dotsource_Paymentoperator_Model_Check_Risk_Storage_Handler
     */
    public function addHandler(Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Interface $handler)
    {
        $this->_handler[] = $handler;
        return $this;
    }


    /**
     * Return all added handlers.
     *
     * @return array
     */
    protected function _getHandlers()
    {
        return $this->_handler;
    }


    /**
     * Return true if no handler are available.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return !$this->_handler;
    }


    /**
     * Performs all handlers in fifo order and return the transformation result.
     *
     * @param mixed $data
     * @return mixed
     */
    public function setTransform($data)
    {
        if (!$this->isEmpty()) {
            /* @var $handler Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Interface */
            foreach ($this->_getHandlers() as $handler) {
                $data = $handler->setData($data);
            }
        }

        return $data;
    }


    /**
     * Performs all handlers in lifo order and return the transformation result.
     *
     * @param mixed $data
     * @return mixed
     */
    public function getTransform($data)
    {
        if (!$this->isEmpty()) {
            /* @var $handler Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Interface */
            foreach (array_reverse($this->_getHandlers()) as $handler) {
                $data = $handler->getData($data);
            }
        }

        return $data;
    }
}