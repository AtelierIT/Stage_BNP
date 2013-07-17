<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Block_Info_Mpass
    extends Dotsource_Paymentoperator_Block_Info_Abstract
{

    /**
     * @see Mage_Payment_Block_Info::_construct()
     */
    protected function _construct()
    {
        parent::_construct();

        //Change the template
        $this->setTemplate('paymentoperator/info/callback.phtml');
    }
}