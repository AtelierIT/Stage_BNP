<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Observer_Billpay_Errormanager
{

    /**
     * Sets the frontend message from the response model.
     *
     * @param   Dotsource_Paymentoperator_Model_Error_Manager  $manager
     */
    public function setFrontendMessageFromResponse(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        $response = $manager->getRequest()->getPaymentoperatorResponse();

        if ($response && $response instanceof Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract) {
            if ($message = $response->getCustomerErrorText()) {
                $manager->getRequest()->setMessage($message);
            }
        }
    }

    /**
     * Sets the backend message from the response model.
     *
     * @param   Dotsource_Paymentoperator_Model_Error_Manager  $manager
     */
    public function setBackendMessageFromResponse(Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        $response = $manager->getRequest()->getPaymentoperatorResponse();

        if ($response && $response instanceof Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract) {
            if ($message = $response->getMerchantErrorText()) {
                $manager->getRequest()->setMessage($message);
            }
        }
    }
}