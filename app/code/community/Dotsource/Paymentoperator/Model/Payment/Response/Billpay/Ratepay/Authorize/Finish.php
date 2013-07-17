<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Ratepay_Authorize_Finish
    extends Dotsource_Paymentoperator_Model_Payment_Response_Billpay_Abstract
{

    /**
     * Don't save the full pdf as string in the log file.
     * @var array
     */
    protected $_dangerousTags           = array('bpinfo1', 'bpinfo2');

    /**
     * Don't encode this fields.
     * @var array
     */
    protected $_useUndecodeParameters   = array('bpinfo1', 'bpinfo2');


    /**
     * Return a base64 encoded pdf file for the customer.
     *
     * @return  string|null
     */
    public function getInfoPdf1()
    {
        return $this->getResponse()->getData('bpinfo1');
    }

    /**
     * Return a base64 encoded pdf file.
     *
     * @return  string|null
     */
    public function getInfoPdf2()
    {
        return $this->getResponse()->getData('bpinfo2');
    }
}