<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Klarna_Financing
    extends Dotsource_Paymentoperator_Model_Payment_Klarna_Abstract
{

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_klarna_financing';

    /** Holds the path to the request models */
    protected $_requestModelInfo    = 'paymentoperator/payment_request_klarna_';

    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_klarna_financing';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paymentoperator/info_klarna_financing';


    /**
     * Retrieve value of klarna action.
     *
     * @return string
     */
    public function getKlarnaAction()
    {
        return $this->getConfigData('action_code');
    }
}