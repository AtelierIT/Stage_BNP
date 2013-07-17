<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
/* @var $this Dotsource_Paymentoperator_Model_Mysql4_Setup */
$this->startSetup();

//Add the Grid attribute for the transaction refnr
$this->addFlatGridAttribute('order', 'paymentoperator_transaction_id', array('type' => 'int'));

$this->endSetup();