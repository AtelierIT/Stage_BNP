<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Cc_Paymentgate
    extends Dotsource_Paymentoperator_Block_Info_Abstract
{

    /**
     * @see Mage_Payment_Block_Info::_construct()
     */
    protected function _construct()
    {
        parent::_construct();

        //Change the template
        $this->setTemplate('paymentoperator/info/callback.phtml');
    }

    /**
     * Return the cc brand name.
     *
     * @return string
     */
    public function getCcTypeName()
    {
        //Check for magento type
        $ccType = $this->getInfo()->getCcType();
        if ($this->_getHelper()->getConverter()->isMagentoCcType($ccType)) {
            $types  = Mage::getSingleton('payment/config')->getCcTypes();
            if (isset($types[$ccType])) {
                return $types[$ccType];
            }
            return (empty($ccType)) ? Mage::helper('payment')->__('N/A') : $ccType;
        }

        return $ccType;
    }

    /**
     * Prepare credit card related payment info
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        //Create a new transport (varien object) object.
        $transport      = Mage_Payment_Block_Info::_prepareSpecificInformation($transport);
        $data           = array();
        $pseudo         = $this->getInfo()->getCcNumberEnc();
        $usePseudoCc    = $this->getInfo()->getMethodInstance()->getConfigData('use_pseudo_data');
        $showPseudoCc   = $this->getInfo()->getCcPrefillInformation()
            || ($this->_getHelper()->isBackend() && $usePseudoCc);

        if ($pseudo && $showPseudoCc) {
            //Paymentgate type
            $data[Mage::helper('payment')->__('Credit Card Type')] = $this->getCcTypeName();

            //Set the pseudo cc number
            if (!$this->getIsSecureMode()) {
                $ccNumber = Mage::helper('core')->getEncryptor()->decrypt(
                    $this->getInfo()->getCcNumberEnc()
                );

                $data[$this->_getHelper()->__('Pseudo Credit Card Nr.')] = $ccNumber;
            } else {
                $data[$this->_getHelper()->__('Credit Card Nr.')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
            }
        }

        return $transport->setData(array_merge($data, $transport->getData()));
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