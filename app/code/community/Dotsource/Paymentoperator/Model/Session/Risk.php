<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Session_Risk
    extends Mage_Core_Model_Session_Abstract
{

    /**
     * Init the risk session.
     */
    protected function _construct()
    {
        $this->init('paymentoperator_risk');
    }
}