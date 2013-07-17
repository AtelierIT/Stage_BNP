<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * mdaehnert - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Clickandbuy
    extends Dotsource_Paymentoperator_Block_Info_Abstract
{

    /**
     * @see Mage_Payment_Block_Info::_construct()
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('paymentoperator/info/callback.phtml');
    }
}
