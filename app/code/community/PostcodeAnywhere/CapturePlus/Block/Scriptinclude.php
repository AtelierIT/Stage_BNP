<?php

class PostcodeAnywhere_CapturePlus_Block_ScriptInclude extends Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
		$this->getLayout()->getBlock('head')->addCss('captureplus/toolkit-3.00.min.css');
        $this->getLayout()->getBlock('head')->addJs('captureplus/address-3.00.min.js');
    }
}
