<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Observer_Address
{

    /**
     * Include the address check ability in the risk check
     *
     * @param $observer
     */
    public function onRiskCheckIsAddressCheckAvailable(Varien_Event_Observer $observer)
    {
        /* @var $oracle Dotsource_Paymentoperator_Model_Oracle_Type_Order */
        $result = $observer->getResult();
        $oracle = $observer->getOracle();

        //Check if the current result is active
        if (!$result->getIsAvailable()) {
            return;
        }

        //Get the address model
        $address = $this->_getAddressModel($oracle);

        //Set if the risk check is available
        $result->setIsAvailable(
            $address->isAvailable()
        );
    }


    /**
     * Include the address check ability in the risk check. This method return
     * check if the address was correct checked.
     *
     * @param $observer
     */
    public function onRiskCheckIsAddressCheckValid(Varien_Event_Observer $observer)
    {
        /* @var $oracle Dotsource_Paymentoperator_Model_Oracle_Type_Order */
        $result = $observer->getResult();
        $oracle = $observer->getOracle();

        //Check if the current result is active
        if (!$result->getIsAvailable()) {
            return;
        }

        //Get the address model
        $address = $this->_getAddressModel($oracle);

        //Set if the risk check is available
        $result->setIsAvailable(
            !$address->isAddressCheckNeeded()
        );
    }


    /**
     * Return the address check model from the registry.
     *
     * @param $oracle
     * @return Dotsource_Paymentoperator_Model_Check_Address_Address
     */
    protected function _getAddressModel(Dotsource_Paymentoperator_Model_Oracle_Type_Order $oracle)
    {
        //Create the registry key for previous address model
        $registryKey    = "address_check_model_{$oracle->getType()}_{$oracle->getModel()->getId()}";
        $address        = Mage::registry($registryKey);

        //If we have no address check model let's create one and store in registry
        if (!$address) {
            //Get a address model
            $address = Mage::getModel('paymentoperator/check_address_address')->init($oracle->getModel());

            //Store in registry
            Mage::register($registryKey, $address);
        }

        return $address;
    }


    /**
     * Process a undefined error.
     *
     * @param $code
     * @param $manager
     */
    public function processUndefinedError($code, Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        /* @var $address Dotsource_Paymentoperator_Model_Check_Address_Address */
        $address = Mage::getModel('paymentoperator/check_address_address');

        //Set the value if we need to block the checkout
        $manager->getRequest()->setLockCheckoutStep((boolean) $address->getConfigData('lock'));
    }


    /**
     * Don't lock customer on error.
     *
     * @param $code
     * @param $manager
     */
    public function processDontLock($code, Dotsource_Paymentoperator_Model_Error_Manager $manager)
    {
        //Disable locking
        $manager->getRequest()->setLockCheckoutStep(false);
    }
}