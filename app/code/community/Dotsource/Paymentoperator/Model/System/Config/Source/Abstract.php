<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 27.04.2010 10:36:18
 *
 * Contributors:
 * dcarl - initial contents
 */

abstract class Dotsource_Paymentoperator_Model_System_Config_Source_Abstract
{

    /**
     * Retrieves the paymentoperator helper.
     *
     * @return  Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}