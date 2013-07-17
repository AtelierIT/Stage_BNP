<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Config_Form_Company
    extends Dotsource_Paymentoperator_Block_Config_Form_Abstract
{

    public function __construct()
    {
        //Key of the legal form
        $this->addColumn('key', array(
            'label'             => $this->_getHelper()->__('Key'),
            'title'             => $this->_getHelper()->__('Key'),
            'style'             => 'width:100px',
            'validate'          => 'required-entry',
        ));

        //Name of the legal form
        $this->addColumn('value', array(
            'label'             => $this->_getHelper()->__('Company Legal Form'),
            'title'             => $this->_getHelper()->__('Company Legal Form'),
            'style'             => 'width:100px',
            'validate'          => 'required-entry',
        ));

        $this->_addAfter        = false;
        $this->_addButtonLabel  = $this->_getHelper()->__('Add New Company Legal Form');

        parent::__construct();

        //Set the right template
        $this->setTemplate('paymentoperator/config/form/field/form.phtml');
    }
}