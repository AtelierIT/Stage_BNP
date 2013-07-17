<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Eft
    extends Dotsource_Paymentoperator_Block_Info_Abstract
{

    /**
     * @see Mage_Payment_Block_Info_Cc::_prepareSpecificInformation()
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        $transport = parent::_prepareSpecificInformation($transport);

        $data[Mage::helper('payment')->__('Account holder')] = $this->getInfo()->getEftOwner();
        $data[Mage::helper('payment')->__('Bank account number')] = sprintf('xxxx-%s', $this->getInfo()->getEftBan4());
        $data[Mage::helper('payment')->__('Bank code number')] = $this->getInfo()->getEftBcn();

        return $transport->setData(array_merge($data, $transport->getData()));
    }
}