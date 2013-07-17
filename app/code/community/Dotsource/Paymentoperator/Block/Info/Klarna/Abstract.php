<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Block_Info_Klarna_Abstract
    extends Dotsource_Paymentoperator_Block_Info_Abstract
{

    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $transport = parent::_prepareSpecificInformation($transport);
        /* @var $info Mage_Payment_Model_Info */
        $info           = $this->getInfo();
        /* @var $method Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract */
        $method         = $info->getMethodInstance();
        $additionalData = array();

        //Check if we need the gender
        if ($method->isGenderNeeded()) {
            $additionalData[$this->__('Gender')] = $method->getGenderLabel($info->getKlarnaGender());
        }

        // Is dob needed?
        if ($method->isDobNeeded()) {
            $additionalData[$this->__('Date Of Birth')] = Mage::app()->getLocale()
                ->date($info->getKlarnaDob(), null, null, false)->get(Zend_Date::DATE_MEDIUM);
        }

        //Check if we need to use the ssn data
        if ($method->isSsnNeeded() || $method->isCommercialRegisterNumberNeeded()) {
            $additionalData[$method->getSsnLabel()] = $info->decrypt($info->getKlarnaSsn());
        }

        $transport = $transport->addData($additionalData);

        return $transport;
    }
}