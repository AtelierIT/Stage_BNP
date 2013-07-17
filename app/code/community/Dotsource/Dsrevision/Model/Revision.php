<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * ewohllebe - initial contents
 */
class Dotsource_Dsrevision_Model_Revision
{

    /* Holds the filename of the revision file */
    const DEPLOY_REVISION_FILENAME = 'DEPLOYED_REVISION';


    /**
     * Return a normalized revision number from the revision file.
     *
     * @return string || false
     */
    public function getRevisionId()
    {
        try {
            //Get the dsrevision file
            $file = realpath(Mage::getBaseDir() . DS . self::DEPLOY_REVISION_FILENAME);

            //Check if we can work with the file
            if (empty($file) || !is_readable($file)) {
                return false;
            }

            $content = file_get_contents($file);

            //TODO: Choose other format to avoid regular expression
            $matches = array();
            preg_match('/\d{5,}/', $content, $matches);

            //Check
            if (empty($matches) || !is_array($matches)) {
                return false;
            }

            //Return the first item
            return rtrim(current($matches));
        } catch (Exception $e) {
            return false;
        }
    }
}