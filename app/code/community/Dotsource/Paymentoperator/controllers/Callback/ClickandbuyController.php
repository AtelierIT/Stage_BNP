<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 13.01.2011 11:39:55
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Callback_ClickandbuyController
    extends Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
{
    /**
     *
     * @return string
     */
    protected function _getPaymentCode()
    {
        return 'paymentoperator_click_and_buy';
    }
}