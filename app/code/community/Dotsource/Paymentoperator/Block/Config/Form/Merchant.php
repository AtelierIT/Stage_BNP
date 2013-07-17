<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Config_Form_Merchant
    extends Dotsource_Paymentoperator_Block_Config_Form_Abstract
{

    public function __construct()
    {
        //Country for option key mapping
        $this->addColumn('currency', array(
            'label'             => $this->_getHelper()->__('Currency'),
            'title' => $this->_getHelper()->__('Currency'),
            'style'             => 'width:160px',
            'renderer'          => new Dotsource_Paymentoperator_Block_Config_Form_Renderer_Select(),
            'values'            => Mage::getSingleton('adminhtml/system_config_source_currency')->toOptionArray(true),
            'class'             => 'currency',
            'validate'          => 'validate-select',
        ));

        //Merchant id
        $this->addColumn('id', array(
            'label'             => $this->_getHelper()->__('Merchant ID'),
            'title' => $this->_getHelper()->__('Merchant ID'),
            'style'             => 'width:100px',
            'validate'          => 'required-entry',
        ));

        //Merchant password
        $this->addColumn('password', array(
            'label' => $this->_getHelper()->__('Merchant Password'),
            'title' => $this->_getHelper()->__('Merchant Encryption Password'),
            array(
                'name'          => 'password',
                'style'         => 'width:120px',
                'input-type'    => 'password',
                'validate'      => 'required-entry',
            ),
            array(
                'name'          => 'original_password',
                'input-type'    => 'hidden',
            ))
        );

        //Merchant hmac
        $this->addColumn('hmac', array(
            'label' => $this->_getHelper()->__('Merchant Hmac'),
            'title' => $this->_getHelper()->__('Merchant Hmac'),
            array(
                'name'          => 'hmac',
                'style'         => 'width:120px',
                'input-type'    => 'password',
                'validate'      => 'required-entry',
            ),
            array(
                'name'          => 'original_hmac',
                'input-type'    => 'hidden',
            ))
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = $this->_getHelper()->__('Add New Merchant');

        parent::__construct();

        //Set the right template
        $this->setTemplate('paymentoperator/config/form/field/form.phtml');
    }
}