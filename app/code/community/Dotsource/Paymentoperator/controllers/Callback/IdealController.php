<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Callback_IdealController
    extends Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
{

    /**
     * @see Dotsource_Paymentoperator_Controller_Paymentoperatorcallback::_getPaymentCode()
     *
     * @return string
     */
    protected function _getPaymentCode()
    {
        return 'paymentoperator_ideal';
    }
}