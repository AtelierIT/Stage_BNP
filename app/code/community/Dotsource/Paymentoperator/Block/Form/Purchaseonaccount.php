<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 11.01.2011 10:26:24
 *
 * Contributors:
 * developer - initial contents
 */
class Dotsource_Paymentoperator_Block_Form_Purchaseonaccount
    extends Dotsource_Paymentoperator_Block_Form_Abstract
{

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('paymentoperator/form/purchaseonaccount.phtml');
    }

    /**
     * Init the logo.
     */
    protected function _initLogos()
    {
        //No image
    }

    /**
     *
     * @return string
     */
    public function getAdditionalText()
    {
        return $this->escapeHtml($this->getMethod()->getConfigData('info_text'));
    }
}