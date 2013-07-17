<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Block_Info_Billpay_Abstract
    extends Dotsource_Paymentoperator_Block_Info_Abstract
{

    /**
     * Return the payment informations.
     *
     * @param   Varien_Object|null   $transport
     * @return  Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        //Return the already cached payment information if available
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }

        /* @var $method Dotsource_Paymentoperator_Model_Payment_Billpay_Abstract */
        $transport      = parent::_prepareSpecificInformation($transport);
        $method         = $this->getInfo()->getMethodInstance();

        //Add the billpay transaction id to the backend
        if (!$this->getIsSecureMode()) {
            $transport[$this->__('Billpay transaction id')] = $method->getBillpayTransactionId();
        }

        //This information are only important for the checkout process information
        if ($method->getOracle()->isQuote()) {
            //Check if we need to show the salutation
            if (!$method->isCompany()) {
                $transport[$this->__('Salutation')] = $method->getSalutationLable();
            } else {
                $transport[$this->__('Company Name')]       = $method->getCompanyName();
                $transport[$this->__('Company Legal Form')] = $method->getCompanyLegalForm();
            }

            //Show dob?
            if ($method->isDobNeeded()) {
                $transport[$this->__('Date Of Birth')] = $method->getDob(
                    Mage::app()->getLocale()->getDateFormat()
                );
            }
        } else {
            //Add the receiver bank details
            if ($method->getEftReceiverBan() && $this->isAdminOrInvoiceContext()) {
                $transport[$this->__('Receiver bank details')]  = "";
                $transport[$this->__('Account holder')]         = $method->getEftReceiverOwner();
                $transport[$this->__('Bank Name')]              = $method->getEftReceiverBankName();
                $transport[$this->__('Bank code number')]       = $method->getEftReceiverBcn();
                $transport[$this->__('Bank account number')]    = $method->getEftReceiverBan();
                $transport[$this->__('Intended purpose')]       = $method->getEftReceiverInvoiceReference();
                $transport[$this->__('Term of payment')]        = $method->getEftReceiverInvoiceDate();
            }
        }

        return $transport;
    }
}