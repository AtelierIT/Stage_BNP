<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 26.05.2010 14:48:25
 *
 * Contributors:
 * dcarl - initial contents
 */
abstract class Dotsource_Paymentoperator_Block_Form_Abstract
    extends Mage_Payment_Block_Form
{

    /**
     * Holds all logos who needs to show.
     *
     * @var array
     */
    protected $_logos = null;


    /**
     * Init all logos.
     */
    protected abstract function _initLogos();


    /**
     * Add a new logo to the form block.
     *
     * @param $url
     * @param $title
     */
    public function addLogo($url, $title)
    {
        //Init the logos array
        if (null === $this->_logos) {
             $this->_logos = array();
        }

        //Add a new logo
        $this->_logos[] = sprintf(
            '<img src="%1$s" alt="%2$s" title="%2$s" class="v-middle paymentlogo" />',
            $this->escapeUrl($url),
            $this->escapeHtml($title)
        );
    }

    /**
     * Return the html of the form image logos.
     *
     * @return string
     */
    public function getLogoHtml()
    {
        //Init the logos
        if (null === $this->_logos) {
            $this->_initLogos();
        }

        if ($this->_logos) {
            return implode('', $this->_logos);
        }

        return '';
    }

    /**
     * TODO: This method only need in magento versions < 1.4.1.0
     * Escape html entities in url
     *
     * @param string $data
     * @return string
     */
    public function escapeUrl($data)
    {
        return $this->helper('core')->urlEscape($data);
    }

    /**
     * Return the for attribute for the image.
     *
     * @return string
     */
    protected function _getForAttribute()
    {
        return "p_method_{$this->getMethod()->getCode()}";
    }

    /**
     * Return the info instance.
     *
     * @return Mage_Payment_Model_Info
     */
    public function getInfoInstance()
    {
        return $this->getMethod()->getInfoInstance();
    }

    /**
     * Returns the name/id of an payment field.
     *
     * @param   string  $field
     * @return  string
     */
    public function getFieldName($field)
    {
        return $this->getMethod()->getFieldName($field);
    }

    /**
     * Return a container identifier for the given field.
     *
     * @param   string  $field
     * @return  string
     */
    public function getContainerName($field)
    {
        return $this->getMethod()->getContainerName($field);
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