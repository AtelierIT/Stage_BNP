<?php
	class Magestore_Onestepcheckout_Block_Admin_Orderlist_Totals extends Mage_Adminhtml_Block_Sales_Totals
	{
		protected function _initTotals()
		{
			parent::_initTotals();			
			if(!$this->getInvoice()->getId()){
				$this->_totals['paid'] = new Varien_Object(array(
					'code'      => 'paid',
					'strong'    => true,
					'value'     => $this->getSource()->getTotalPaid(),
					'base_value'=> $this->getSource()->getBaseTotalPaid(),
					'label'     => $this->helper('sales')->__('Total Paid'),
					'area'      => 'footer'
				));
				$this->_totals['refunded'] = new Varien_Object(array(
					'code'      => 'refunded',
					'strong'    => true,
					'value'     => $this->getSource()->getTotalRefunded(),
					'base_value'=> $this->getSource()->getBaseTotalRefunded(),
					'label'     => $this->helper('sales')->__('Total Refunded'),
					'area'      => 'footer'
				));
				$this->_totals['due'] = new Varien_Object(array(
					'code'      => 'due',
					'strong'    => true,
					'value'     => $this->getSource()->getTotalDue(),
					'base_value'=> $this->getSource()->getBaseTotalDue(),
					'label'     => $this->helper('sales')->__('Total Due'),
					'area'      => 'footer'
				));
			}
			return $this;
		}	
		
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