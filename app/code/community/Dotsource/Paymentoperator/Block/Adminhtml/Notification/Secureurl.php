<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Adminhtml_Notification_Secureurl
    extends Mage_Adminhtml_Block_Notification_Baseurl
{

    /**
     * @see Mage_Adminhtml_Block_Notification_Baseurl::getConfigUrl()
     *
     * @return string
     */
    public function getConfigUrl()
    {
        //Holds the route to the setting
        $route = 'adminhtml/system_config/edit';
        $param = array('section' => 'web');

        //check for default settings
        $defaultSecureUrl  = (string) Mage::getConfig()->getNode(
            'default/'.Mage_Core_Model_Store::XML_PATH_SECURE_BASE_URL
        );

        if (!$this->_getHelper()->isHttpsUrl($defaultSecureUrl)) {
            return $this->getUrl($route, $param);
        }

        //Find all secure urls for checking the https
        /* @var $dataCollection Mage_Core_Model_Mysql4_Config_Data_Collection */
        $dataCollection = Mage::getResourceModel('core/config_data_collection')
            ->addFieldToFilter('path', array('eq' => Mage_Core_Model_Store::XML_PATH_SECURE_BASE_URL));

        //Add filter for start with https
        $dataCollection
            ->getSelect()
            ->where(new Zend_Db_Expr("LOWER(main_table.value) NOT LIKE ?"), 'https://%')
            ->limit(1);

        //Check for the right section
        foreach ($dataCollection as $data) {
            //Check if the given url is an https url
            if ($this->_getHelper()->isHttpsUrl($data->getValue())) {
                continue;
            }

            //Build the right url
            switch ($data->getScope()) {
                case 'default' :
                    return $this->getUrl($route, $param);
                case 'stores' :
                    $param['store'] = Mage::app()->getStore($data->getScopeId())->getCode();
                    return $this->getUrl($route, $param);
                case 'websites' :
                    $param['website'] = Mage::app()->getWebsite($data->getScopeId())->getCode();
                    return $this->getUrl($route, $param);
            }
        }

        return "";
    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}