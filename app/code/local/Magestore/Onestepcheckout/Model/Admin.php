<?php

class Magestore_Onestepcheckout_Model_Admin extends Mage_Core_Model_Abstract
{

    public function _construct()	
	{
		parent::_construct();
		$this->_init('onestepcheckout/admin');
	}	
	
	public function skipPaymentMethod($observers)
	{			
		$result = $observers->getResult();	
		$methodInstance = $observers->getMethodInstance();
		$cookie = Mage::getSingleton('core/cookie');
		$key = $cookie->get('onestepcheckout_admin_key');
		$code = $cookie->get('onestepcheckout_admin_code');
		$adminId = $cookie->get('onestepcheckout_admin_id');
		$action = Mage::app()->getRequest()->getControllerName();//getControllerName();
		$module = Mage::app()->getRequest()->getModuleName();		
		if($key && $code && $adminId && $module =='onestepcheckout' && $action=='admin'){
			if($methodInstance->getCode() == 'checkmo' || $methodInstance->getCode() == 'ccsave'){
				$result->isAvailable = true;
				$result->isDeniedInConfig = false;			
			}else{
				$result->isAvailable = false;
				$result->isDeniedInConfig = true;			
			}
		}
		return $this;
	}
}