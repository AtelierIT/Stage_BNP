<?php
	class Magestore_Onestepcheckout_Block_Admin_Orderlist_Orderlist extends Mage_Core_Block_Template
	{
		
		public function _construct()
		{	
			parent::_construct();
		}
		
		public function getOrderCollection()
		{
			$collection = null;
			$collection = Mage::getModel('sales/order')->getCollection();
			return $collection;
		}
		
		public function getOrderbyId($orderId)
		{
			$order = Mage::getModel('sales/order')->load($orderId);
			return $order;
		}
		
		public function getOrderSearchByCustomerEmail($key)
		{
			$orderIds = array();
			$collections = Mage::getModel('sales/order')->getCollection();
			$collections->getSelect()->where('main_table.customer_firstname like "%'.$key.'%"
								OR main_table.customer_lastname like "%'.$key.'%"
								OR main_table.customer_middlename like "%'.$key.'%"
								OR main_table.customer_email like "%'.$key.'%"
								');
			return $collections->getAllIds();		
		}
		
		public function getOrderSearchById($orderId)
		{
			$orderIds = array(); 
			$collections = Mage::getModel('sales/order')->getCollection()
											->addFieldToFilter('increment_id', array('like'=>'%'.$orderId.'%'));
			return $collections->getAllIds();			
		}

		public function getOrderGridCollections()
		{
			$storeId = Mage::app()->getStore()->getId();
			$rows = Mage::getStoreConfig('onestepcheckout/admin/rows', $storeId);
			$orderId = $this->getRequest()->getParam('order_id');
			$email = $this->getRequest()->getParam('name_email');
			$collection  = Mage::getResourceModel('sales/order_grid_collection')												
												->setOrder('entity_id', 'DESC');
			if($rows)
				$collection = $collection->setPageSize($rows);
			if($orderId)
				$collection = $collection->addFieldToFilter('entity_id', array('in'=>$this->getOrderSearchById($orderId)));
			if($email){				
				$collection = $collection->addFieldToFilter('entity_id', array('in'=>$this->getOrderSearchByCustomerEmail($email)));				
			}
			return $collection;
			
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
		
		public function getSearchUrl(){
			return $this->getUrl('onestepcheckout/index/orderlistSearch');
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