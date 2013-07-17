<?php

class Magestore_Onestepcheckout_Block_Product_Grouped extends Magestore_Onestepcheckout_Block_Product_View
{
	public function _prepareLayout(){
		parent::_prepareLayout();
		$this->setTemplate('onestepcheckout/admin/checkout/product/grouped.phtml');
		return $this;
	}
	
	public function getStartFormHtml(){
		return $this->getBlockHtml('product.info.grouped');
	}
	
	public function getOptionsWrapperBottomHtml(){
		return $this->getBlockHtml('product.info.addtocart');
	}
}