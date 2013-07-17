<?php

$setup = $this;
$installer = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->startSetup();

$installer->removeAttribute('catalog_category', 'old_id');
$installer->addAttribute('catalog_category', 'old_id', array(
    'input' => 'text',
    'type' => 'text',
    'label' => 'Old id',
    'visible' => true,
    'required' => false,
    'visible_on_front' => false,
    'searchable' => false,
    'used_in_product_listing' => false,
));

$installer->endSetup();