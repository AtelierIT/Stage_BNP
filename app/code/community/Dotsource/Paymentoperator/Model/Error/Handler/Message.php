<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Error_Handler_Message
    extends Dotsource_Paymentoperator_Model_Error_Handler_Abstract
{

    /** Holds the default message section */
    const DEFAULT_MESSAGE_SECTION   = 'default';

    /** const for the backend area error messages */
    const BACKEND                   = 'backend';

    /** const for the frontend area error messages */
    const FRONTEND                  = 'frontend';

    /** Holds the string for the default messages */
    const DEFAULT_MESSAGE           = 'fallback-error-msg';


    /**
     * Holds a list of xpath expressions to resolve a message from the error code.
     *
     * @var array
     */
    protected static $_xpathRoutes  = array(
        '//%1$s/error[normalize-space(@category)="%3$s" and normalize-space(@code)="%2$s"][1]',
        '//%1$s/error[normalize-space(@category)="%3$s" and (starts-with(normalize-space(@code), "%2$s ") or contains(normalize-space(@code), " %2$s ") or substring(normalize-space(@code), string-length(normalize-space(@code)) - %4$s) = " %2$s")][1]',
        '//%1$s/error[normalize-space(@category)="%3$s" and not(@code)][1]',
        '//%1$s/error[normalize-space(@code)="%2$s" and not(@category)][1]',
        '//%1$s/error[not(@category) and (starts-with(normalize-space(@code), "%2$s ") or contains(normalize-space(@code), " %2$s ") or substring(normalize-space(@code), string-length(normalize-space(@code)) - %4$s) = " %2$s")][1]',
    );

    /**
     * Holds the simple xml object.
     *
     * @var SimpleXMLElement
     */
    protected static $_errorCodes   = null;


    /**
     * Holds the full error code.
     *
     * @var string|null
     */
    protected $_code                = null;
    /**
     * Holds the error code.
     *
     * @var string|null
     */
    protected $_errorCode           = null;

    /**
     * Holds the category of the error code.
     *
     * @var string|null
     */
    protected $_categoryErrorCode   = null;


    /**
     * Init some default
     */
    protected function _construct()
    {
        parent::_construct();

        //Callback
        if (!$this->hasMessageCallbackAlias()) {
            $this->setMessageCallbackAlias('callback');
        }

        //Init a default message
        if (!$this->hasDefaultMessage()) {
            $this->setDefaultMessage(
                'There was an undocumented error by processing your payment. '.
                'Please try again or choose another payment method.'
            );
        }

        //Fallback to default
        if (!$this->hasFallback()) {
            $this->setFallback(true);
        }
    }

    /**
     * @see Dotsource_Paymentoperator_Model_Error_Handler_Interface::processHandler()
     *
     * @param Varien_Object $request
     */
    public function processHandler(Varien_Object $request)
    {
        //Holds the current message
        $message = $request->getMessage();

        //Get the message from the code
        if ($this->isCode()) {
            //set the message as error code
            $this->setCode($message);

            //Process the error code
            if ($this->hasMessageFromCode()) {
                //Get the message from the code
                $request->setMessage($this->getMessageFromCode());
                $this->updateCallbackFromCode();
            } else {
                //Fallback to the default error message
                $this->setCode(self::DEFAULT_MESSAGE);

                //check for fallback error code
                if ($this->hasMessageFromCode()) {
                    $msg = $this->getMessageFromCode();
                    $this->updateCallbackFromCode();
                } else {
                    //Try to get a message from the current model or a default message
                    $msg = $this->getDefaultMessage();
                }

                //Replace the current message code
                $request
                    ->setMessage($msg)
                    ->setArguments(array());
            }
        }
    }

    /**
     * Check if the message is an code.
     *
     * @return  boolean
     */
    public function isCode()
    {
        return (boolean) $this->_getManager()->getRequest()->getIsErrorCode();
    }

    /**
     * Set the error code.
     *
     * @return   Dotsource_Paymentoperator_Model_Error_Handler_Message
     */
    public function setCode($errorCode)
    {
        if ($errorCode === self::DEFAULT_MESSAGE) {
            $this->_code                = $errorCode;
            $this->_errorCode           = $errorCode;
        } else {
            $this->_code                = $errorCode;
            $this->_errorCode           = substr($errorCode, -4);
            $this->_categoryErrorCode   = substr($errorCode, 1, 3);
        }

        return $this;
    }

    /**
     * Return the full error code.
     *
     * @return  string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * Return the last 4 chars of the full error code.
     *
     * @return  string
     */
    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    /**
     * Return the error category of the full code.
     *
     * @return  string
     */
    public function getCategoryCode()
    {
        return $this->_categoryErrorCode;
    }

    /**
     * Return the message section. If no section area available in the request
     * we return the default message section.
     *
     * @return string
     */
    protected function _getMessageSection()
    {
        $request = $this->_getManager()->getRequest();

        //Get the special message section
        if ($request->getMessageSection()) {
            return $request->getMessageSection();
        }

        return self::DEFAULT_MESSAGE_SECTION;
    }

    /**
     * Return the specific magento error area.
     *
     * @return string
     */
    protected function _getSectionArea()
    {
        //Get the request to check for a special area
        $request    = $this->_getManager()->getRequest();

        //Get a special area from the request
        if ($request->getSectionArea()) {
            return $request->getSectionArea();
        }

        //Auto detect
        if ($this->_getHelper()->isFrontend()) {
            return self::FRONTEND;
        } else {
            return self::BACKEND;
        }
    }

    /**
     * Check if we have an error tag to the error code.
     *
     * @return  boolean
     */
    public function hasMessageFromCode()
    {
        $message = $this->getMessageFromCode();
        return is_string($message);
    }

    /**
     * Return the message from the error code.
     *
     * @return string
     */
    public function getMessageFromCode()
    {
        //Get the xml error node
        $node = $this->_getXmlErrorNode();

        //Process the node of exists
        if ($node) {
            return (String)$node;
        }

        return null;
    }

    /**
     * Process a error code callback.
     */
    public function updateCallbackFromCode()
    {
        //Check if we could use callbacks
        if (!$this->_getManager()->hasHandler($this->getMessageCallbackAlias())) {
            return;
        }

        //Get the xml error node
        $node = $this->_getXmlErrorNode();

        //Process the node of exists
        if ($node && $node->attributes()->callback) {
            $this->_getManager()->updateHandler(
                $this->getMessageCallbackAlias(),
                'setCallback',
                (String)$node->attributes()->callback
            );
        }
    }

    /**
     * Return the error node to the given error code.
     *
     * @return SimpleXMLElement || null
     */
    protected function _getXmlErrorNode()
    {
        //Holds the available message paths
        $messagePaths = array();

        //Get the specific message path
        $messageSection = $this->_getMessageSection();
        $sectionArea    = $this->_getSectionArea();

        //Add the path with the specific values
        $messagePaths[] = "{$messageSection}/{$sectionArea}";

        //If the first path was not the default path add the default path to as fallback path
        if ($this->getFallback() && self::DEFAULT_MESSAGE_SECTION !== $messageSection) {
            $messagePaths[] = self::DEFAULT_MESSAGE_SECTION . "/{$sectionArea}";
        }

        //Check for the error codes
        foreach ($messagePaths as $messagePath) {
            foreach (self::$_xpathRoutes as $xpath) {
                //Get a specific error message
                $message = $this->_getXml()->xpath(
                    sprintf(
                        "$xpath",
                        $messagePath,
                        $this->getErrorCode(),
                        $this->getCategoryCode(),
                        strlen($this->getErrorCode()) //Don't substact 1 we need to calc. one extra space
                    )
                );

                //return the first message
                if (is_array($message) && $message) {
                    return current($message);
                }
            }
        }

        return null;
    }

    /**
     * Return the xml object with all error codes.
     *
     * @return SimpleXMLElement
     */
    protected function _getXml()
    {
        //Get the xml object
        if (null === self::$_errorCodes) {

            //Get the error file
            $errorFile = Mage::getConfig()->getModuleDir('etc', 'Dotsource_Paymentoperator').DS.'errors.xml';

            //Set the xml object
            self::$_errorCodes = simplexml_load_file($errorFile);
        }

        return self::$_errorCodes;
    }

    /**
     * Set a specific xml for the error codes. This is needed for phpunit tests.
     *
     * @param   string  $xml
     */
    public static function setXml($xml)
    {
        self::$_errorCodes = simplexml_load_string($xml);
    }

    /**
     * Return the result from the given xpath.
     *
     * @param   string  $xpath
     * @return  SimpleXMLElement
     */
    public static function xpath($xpath)
    {
        return self::$_errorCodes->xpath($xpath);
    }
}