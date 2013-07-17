<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 13.01.2011 09:07:08
 *
 * Contributors:
 * mdaehnert - initial contents
 */

class Dotsource_Paymentoperator_Block_Form_Clickandbuy
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        //Template
        $this->setTemplate('paymentoperator/form/clickandbuy.phtml');
    }

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        $this->addLogo(
            $this->getSkinUrl('images/paymentoperator/paymentoperator_click_and_buy.png'),
            $this->_getHelper()->__('ClickandBuy')
        );
    }
}