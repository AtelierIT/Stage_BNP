<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Translate
    extends Mage_Core_Block_Template
{

    /** Holds a array witch all translations */
    protected $_translations = array();


    /**
     * Set the need to translate strings.
     */
    protected function _construct()
    {
        //Add a translations
        $this->addText("The request can't be processed correctly. Please try again.");
    }


    /**
     * Return true if we have data to translate.
     *
     * @return boolean
     */
    public function hasTranslations()
    {
        return $this->getTranslations() && is_array($this->getTranslations());
    }


    /**
     * Add a text who needs to translate.
     *
     * @param $text
     * @return Dotsource_Paymentoperator_Block_Translate
     */
    public function addText($text)
    {
        $this->_translations[$text] = $this->_getHelper()->__($text);
        return $this;
    }


    /**
     * Return an array with all translations.
     *
     * @return array
     */
    public function getTranslations()
    {
        return $this->_translations;
    }


    /**
     * Return the module helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}