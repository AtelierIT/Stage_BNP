<?php

class Magestore_Onestepcheckout_Block_Product_View extends Mage_Catalog_Block_Product_View
{
	public function _prepareLayout(){
		$this->setTemplate('onestepcheckout/admin/checkout/product/view.phtml');
		return parent::_prepareLayout();
	}
	
	public function getJsItems(){
		if (!$this->hasData('js_items')){
			$jsItems = array();
			if ($headBlock = $this->getLayout()->getBlock('head')){
				$designPackage = Mage::getDesign();
				$baseJsUrl = Mage::getBaseUrl('js');
				$mergeCallback = Mage::getStoreConfigFlag('dev/js/merge_files') ? array(Mage::getDesign(), 'getMergedJsUrl') : null;
				foreach ($headBlock->getData('items') as $item){
					$name = $item['name'];
					if ($item['type'] == 'js'){
						$jsItems[] = $mergeCallback ? Mage::getBaseDir().DS.'js'.DS .$name : $baseJsUrl.$name;
					}
					if ($item['type'] == 'skin_js'){
						$jsItems[] = $mergeCallback ? $designPackage->getFilename($name,array('_type' => 'skin')) : $designPackage->getSkinUrl($name,array());
					}
				}
			}
			$this->setData('js_items',$jsItems);
		}
		return $this->getData('js_items');
	}
	
	public function getStartFormHtml(){
		return '';
	}
	
	public function getOptionsWrapperHtml(){
		return $this->getBlockHtml('product.info.options.wrapper');
	}
	
	public function getOptionsWrapperBottomHtml(){
		return $this->getBlockHtml('product.info.options.wrapper.bottom');
	}
	
	public function getEndFormHtml(){
		return '';
	}
}