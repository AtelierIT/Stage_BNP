<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
    implements Dotsource_Paymentoperator_Model_Stacktrace_Interface
{

    /**
     * Holds all access keys for the stack trace and the object configuration.
     */
    const KEY_OBJECT                            = 'object';
    const KEY_CLASS                             = 'class';
    const KEY_FUNCTION_NAME                     = 'function';
    const KEY_CALL_TYPE                         = 'type';
    const KEY_FILE                              = 'file';
    const KEY_LINE                              = 'line';

    /**
     * Holds the call types for the call type configuration.
     */
    const CALL_TYPE_METHOD                      = '->';
    const CALL_TYPE_STATIC                      = '::';


    /**
     * Holds the compare callbacks for the keys.
     */
    protected static $_stackTraceKeyCallbacks   = array(
        self::KEY_CLASS             => '_equal',
        self::KEY_FUNCTION_NAME     => '_equal',
        self::KEY_OBJECT            => '_instanceOf',
        self::KEY_CALL_TYPE         => '_equal',
        self::KEY_LINE              => '_equal',
        self::KEY_FILE              => '_equal',
    );


    /**
     * Holds the stack frame information for that need to match.
     * @var array
     */
    protected $_information                     = array();

    /**
     * Caches a calculated cache key.
     * @var string|null
     */
    protected $_cacheKey                        = null;


    /**
     * check if the given stack trace
     *
     * @param   array   $stackTrace
     * @return  boolean
     */
    public function match(array $stackTrace)
    {
        //Default assumption
        $match = false;

        //Get the top element of the stack
        $topStackFrame = reset($stackTrace);

        //Check all defined rules
        foreach ($this->_information as $key => $value) {
            //Check if the key also exists in the stack trace
            if (!isset($topStackFrame[$key])) {
                $match = false;
                break;
            }

            $callback   = array($this, self::$_stackTraceKeyCallbacks[$key]);
            $parameters = array($topStackFrame[$key], $value);
            $match      = call_user_func_array($callback, $parameters);
            if (!$match) {
                break;
            }
        }

        return $match;

    }

    /**
     * Set a object that need to match.
     *
     * @param   string|object   $object
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setObject($object)
    {
        return $this->_setInformation(self::KEY_OBJECT, $object);
    }

    /**
     * Set a class name that need to match.
     *
     * @param   string  $class
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setClass($class)
    {
        return $this->_setInformation(self::KEY_CLASS, (string) $class);
    }

    /**
     * Set the method/function name that need to match.
     *
     * @param   string  $name
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setMethodName($name)
    {
        return $this->setFunctionName($name);
    }

    /**
     * Set the function/method name that need to match.
     *
     * @param   string  $name
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setFunctionName($name)
    {
        return $this->_setInformation(self::KEY_FUNCTION_NAME, (string) $name);
    }

    /**
     * Sets the call type that need to match. the valid values are:
     *  - CALL_TYPE_METHOD
     *  - CALL_TYPE_STATIC
     *
     * @param   string  $type
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setCallType($type)
    {
        return $this->_setInformation(self::KEY_CALL_TYPE, (string) $type);
    }

    /**
     * Set a files that need to match.
     *
     * @param   string  $file
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setFile($file)
    {
        return $this->_setInformation(self::KEY_FILE, (string) $file);
    }

    /**
     * Set a line that need to match.
     *
     * @param   integer $line
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    public function setLine($line)
    {
        return $this->_setInformation(self::KEY_LINE, (int) $line);
    }

    /**
     * Set the information and clean the cache key.
     *
     * @param   string  $key
     * @param   string  $value
     * @return  Dotsource_Paymentoperator_Model_Stacktrace_Singleframe
     */
    protected function _setInformation($key, $value)
    {
        $this->_information[$key]   = $value;
        $this->_cacheKey            = null;
        return $this;
    }

    /**
     * Compare the given values.
     *
     * @param   mixed $a
     * @param   mixed $b
     * @return  boolean
     */
    protected function _compare($a, $b)
    {
        return $a == $b;
    }

    /**
     * Compare the given values.
     *
     * @param   mixed $a
     * @param   mixed $b
     * @return  boolean
     */
    protected function _equal($a, $b)
    {
        return $a === $b;
    }

    /**
     * Checks if the given object is an instance of the given class.
     *
     * @param   object        $object
     * @param   string|object $class
     * @return  boolean
     */
    protected function _instanceOf($object, $class)
    {
        return $object instanceof $class;
    }

    /**
     * Return the cache key for the object.
     *
     * @return  string
     */
    public function getCacheKey()
    {
        if (null === $this->_cacheKey) {
            //Copy the variable
            $informationCopy = $this->_information;

            //If an object is given we need the class name
            if (isset($informationCopy[self::KEY_OBJECT])
                && !is_string($informationCopy[self::KEY_OBJECT])
            ) {
                $informationCopy[self::KEY_OBJECT] = get_class($informationCopy[self::KEY_OBJECT]);
            }

            //Create the cache key
            foreach ($informationCopy as $key => $value) {
                $this->_cacheKey .= "$key=$value;";
            }
        }

        return $this->_cacheKey;
    }
}