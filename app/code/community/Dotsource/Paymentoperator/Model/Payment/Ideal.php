<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Ideal
    extends Dotsource_Paymentoperator_Model_Payment_Giropay
{

    protected $_canCapture              = false;

    protected $_canCapturePartial       = false;

    protected $_canRefund               = false;

    protected $_canRefundInvoicePartial = false;

    protected $_canVoid                 = false;

    /** Holds the block source path */
    protected $_formBlockType       = 'paymentoperator/form_ideal';

    /** Holds the info source path */
    protected $_infoBlockType       = 'paymentoperator/info_ideal';

    /** Holds the payment code */
    protected $_code                = 'paymentoperator_ideal';

    /** Holds the path to the request models */
    protected $_requestModelInfo    = 'paymentoperator/payment_request_ideal_';


    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Giropay::validate()
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Giropay
     */
    public function validate()
    {
        return true;
    }
}