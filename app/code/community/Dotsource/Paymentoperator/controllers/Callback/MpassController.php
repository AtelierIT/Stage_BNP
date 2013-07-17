<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 30.04.2010 17:10:23
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Callback_MpassController
    extends Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
{

    /**
     * @see Dotsource_Paymentoperator_Controller_Paymentoperatorcallback::_getPaymentCode()
     *
     * @return string
     */
    protected function _getPaymentCode()
    {
        return 'paymentoperator_mpass';
    }
}