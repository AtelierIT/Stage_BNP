<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Url
{
    /** Holds a cache for url => object mapping */
    protected static $_objectCache = array();


    /**
     * Return an Zend_Uri_Http object from the given url.
     *
     * @param string $url
     * @return Zend_Uri_Http
     */
    protected static function getUrlObject($url)
    {
        if (isset(self::$_objectCache[$url])) {
            return self::$_objectCache[$url];
        }

        //Parse and validate the url
        self::$_objectCache[$url] = Zend_Uri_Http::fromString($url);
        return self::$_objectCache[$url];
    }

    /**
     * Return the given parameter from the given key.
     *
     * @param   string  $url
     * @param   string  $key
     * @param   boolean $ignoreCase
     * @return  string|null
     */
    public static function getQueryParameter($url, $key, $ignoreCase = true, $default = null)
    {
        //Get the object
        $urlObject = self::getUrlObject($url);

        //Get all query parameters
        $queryParameters = $urlObject->getQueryAsArray();
        if ($queryParameters) {
            //Convert all to lower
            if ($ignoreCase) {
                $key                = strtolower($key);
                $queryParameters    = array_change_key_case($queryParameters, CASE_LOWER);
            }

            //Return the value if its available
            if (isset($queryParameters[$key])) {
                return $queryParameters[$key];
            }
        }

        return $default;
    }
}