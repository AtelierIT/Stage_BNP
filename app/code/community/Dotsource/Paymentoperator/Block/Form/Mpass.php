<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 30.04.2010 16:42:39
 *
 * Contributors:
 * dcarl - initial contents
 */

class Dotsource_Paymentoperator_Block_Form_Mpass
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        //Template
        $this->setTemplate('paymentoperator/form/mpass.phtml');
    }

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        $this->addLogo(
            $this->getSkinUrl('images/paymentoperator/paymentoperator_mpass.png'),
            $this->_getHelper()->__('Mpass')
        );
    }
}