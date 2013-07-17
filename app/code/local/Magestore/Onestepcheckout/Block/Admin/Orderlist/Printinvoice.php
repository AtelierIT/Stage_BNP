<?php
	class Magestore_Onestepcheckout_Block_Admin_Orderlist_Printinvoice extends Mage_Sales_Block_Order_Print_Invoice
	{			
		public function getOrder()
		{
			$orderId = $this->getRequest()->getParam('order_id');
			$order = Mage::getModel('sales/order')->load($orderId);			
			return $order;
		}
		
		public function getInvoice()
		{
		   $orderId = $this->getRequest()->getParam('order_id');
		   $invoice = Mage::getModel('sales/order_invoice')->load($orderId,'order_Id');
		   return $invoice;
	   } 
	}				
?>