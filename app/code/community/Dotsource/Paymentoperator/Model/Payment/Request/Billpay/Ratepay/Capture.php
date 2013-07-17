<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Ratepay_Capture
    extends Dotsource_Paymentoperator_Model_Payment_Request_Billpay_Capture
{

    /**
     * Return the used object for parsing the response.
     *
     * @return string
     */
    public function getResponseModelCode()
    {
        return "paymentoperator/payment_response_billpay_ratepay_capture";
    }
}