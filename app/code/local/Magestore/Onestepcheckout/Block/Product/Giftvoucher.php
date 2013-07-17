<?php

class Magestore_Onestepcheckout_Block_Product_Giftvoucher extends Magestore_Onestepcheckout_Block_Product_View
{
	public function _prepareLayout(){
		parent::_prepareLayout();
		$this->setTemplate('onestepcheckout/admin/checkout/product/giftvoucher.phtml');
		return $this;
	}
	
	public function getStartFormHtml(){
		return $this->getBlockHtml('product.info.giftvoucher');
	}
	
	public function getOptionsWrapperBottomHtml(){
		return $this->getBlockHtml('product.info.addtocart');
	}
}