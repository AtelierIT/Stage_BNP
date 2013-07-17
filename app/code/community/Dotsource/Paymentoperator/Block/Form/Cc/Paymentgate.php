<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Form_Cc_Paymentgate
    extends Dotsource_Paymentoperator_Block_Form_Cc_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentoperator/form/cc/paymentgate.phtml');
    }
}