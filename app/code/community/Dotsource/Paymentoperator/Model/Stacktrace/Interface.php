<?php
/**
 * Copyright (c) 2008-2012 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
interface Dotsource_Paymentoperator_Model_Stacktrace_Interface
{

    /**
     * Check if the given stack trace matches the configured object.
     *
     * @param   array   $stackTrace
     * @return  boolean
     */
    public function match(array $stackTrace);

    /**
     * Return the cache key for the given object.
     *
     * @return  string
     */
    public function getCacheKey();
}