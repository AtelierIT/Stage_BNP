<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Observer_Cc_Pci
{

    /**
     * If the given response has an pseudo cc number and the "use pseudo data" option
     * is active we save the cc number.
     */
    public function setCreditCardPaymentInformationFromResponse(Varien_Event_Observer $observer)
    {
        /* @var $method     Dotsource_Paymentoperatorextension_Model_Payment_Cc_Pci */
        /* @var $request    Dotsource_Paymentoperatorextension_Model_Payment_Request_Cc_Pci_Authorize */
        /* @var $response   Dotsource_Paymentoperator_Model_Payment_Response_Response */
        $method         = $observer->getMethod();
        $request        = $observer->getRequest();
        $response       = $observer->getResponse();
        $paymentInfo    = $method->getInfoInstance();

        //Check if we should save this data and we have the data from the request
        if ($method instanceof Dotsource_Paymentoperator_Model_Payment_Cc_Abstract
            && $method->isUsePseudoDataActive()
            && $response->getResponse()->getPcnr()
        ) {
            $paymentInfo
                ->setCcNumberEnc(
                    Mage::helper('core')->getEncryptor()->encrypt(
                        $response->getResponse()->getPcnr()
                    )
                )
                ->setCcNumber($response->getResponse()->getPcnr())
                ->setCcLast4(substr($response->getResponse()->getPcnr(), -3));
        }
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