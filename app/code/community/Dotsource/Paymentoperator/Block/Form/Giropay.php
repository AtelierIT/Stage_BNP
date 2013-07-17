<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Form_Giropay
    extends Dotsource_Paymentoperator_Block_Form_Eft
{

    protected function _construct()
    {
        parent::_construct();

        //Change the template
        $this->setTemplate('paymentoperator/form/giropay.phtml');
    }

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        $this->addLogo(
            $this->getSkinUrl('images/paymentoperator/paymentoperator_giropay.png'),
            $this->_getHelper()->__('Giropay')
        );
    }
}