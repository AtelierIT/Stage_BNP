<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Stacktrace
{

    /**
     * Holds already cached results.
     * @var array
     */
    protected static $_cache    = array();


    /**
     * Check if the given criteria match the with the current stack trace.
     *
     * @param   Dotsource_Paymentoperator_Model_Stacktrace_Interface   $criteria
     * @param   array                                           $stackTrace
     * @return  boolean
     */
    public static function match(Dotsource_Paymentoperator_Model_Stacktrace_Interface $criteria, $stackTrace = null)
    {
        //Get the current stack trace if no one is given
        if (null === $stackTrace) {
            $stackTrace = self::getStrackTrace();
        }

        //If the stack trace is empty we don't found the given criteria
        if (!$stackTrace) {
            return false;
        }

        //Check if the criteria matches the stack trace
        $match = $criteria->match($stackTrace);
        if ($match) {
            return true;
        }

        //Remove the first element and do a recursion
        array_shift($stackTrace);
        return self::match($criteria, $stackTrace);
    }

    /**
     * Check if the given criteria match the with the current stack trace.
     *
     * @param   array   $criteria
     * @param   array   $stackTrace
     * @return  boolean
     */
    public static function matchOneOf(array $criteria, $stackTrace = null)
    {
        //Get the current stack trace if no one is given
        if (null === $stackTrace) {
            $stackTrace = self::getStrackTrace();
        }

        //If the stack trace is empty we don't found the given criteria
        if (!$stackTrace) {
            return false;
        }

        //Check if the criteria matches the stack trace
        /* @var $criterion Dotsource_Paymentoperator_Model_Stacktrace_Interface */
        foreach ($criteria as $criterion) {
            $match = $criterion->match($stackTrace);
            if ($match) {
                return true;
            }
        }

        //Remove the first element and do a recursion
        array_shift($stackTrace);
        return self::matchOneOf($criteria, $stackTrace);
    }

    /**
     * Return the current stack trace.l
     *
     * @return array
     */
    public static function getStrackTrace()
    {
        return debug_backtrace();
    }
}