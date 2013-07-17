<?php
	class Magestore_Onestepcheckout_Block_Admin_Orderlist_Vieworder extends Mage_Core_Block_Template
	{
		public function _prepareLayout ()
		{			
			return parent::_prepareLayout();
		}
		public function getEmail($orderId)
		{
			$order = Mage::getModel('sales/order')->load($orderId);
			return $order->getCustomerEmail();
			
		}
		
		public function hasInvoice($Id)
		{
			$status = Mage::getModel('sales/order')->load($Id)->getStatus();
			if($status == 'processing'){
				return true;
			}
			return false;
		}			
		
		public function getSearchUrl()
		{
			return $this->getUrl('onestepcheckout/index/orderlistSearch');
		}
		
		public function getOrder()
		{
			$orderId = $this->getRequest()->getParam('order_id');
			$order = Mage::getModel('sales/order')->load($orderId);
			return $order;
		}
		
		public function getOrderStoreName($order)
		{
			if ($order) {
				$storeId = $order->getStoreId();
				if (is_null($storeId)) {
					$deleted = Mage::helper('onestepcheckout')->__(' [deleted]');
					return nl2br($order->getStoreName()) . $deleted;
				}
				$store = Mage::app()->getStore($storeId);
				$name = array(
					$store->getWebsite()->getName(),
					$store->getGroup()->getName(),
					$store->getName()
				);
				return implode('<br/>', $name);
			}
			return null;
		}
		
		public function getCustomerGroupName($order)
		{
			if ($order) {
				return Mage::getModel('customer/group')->load((int)$order->getCustomerGroupId())->getCode();
			}
			return null;
		}
		
	
	}
?>