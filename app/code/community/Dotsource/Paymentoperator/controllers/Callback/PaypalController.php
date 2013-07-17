<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 28.04.2010 14:40:18
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Callback_PaypalController
    extends Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
{

    /**
     * Retreives the code for the paymentmethod.
     * @see Dotsource_Paymentoperator_Controller_Paymentoperatorcallback::_getPaymentCode()
     *
     * @return  string
     */
    protected function _getPaymentCode()
    {
        return 'paymentoperator_paypal_standard';
    }
}