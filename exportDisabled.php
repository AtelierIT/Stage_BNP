<?php

$profileId = 9; // Enter the export profile number from System > Import/Export > Profiles

require_once('app/Mage.php');
umask(0);
Mage::app('admin');

$profile = Mage::getModel('dataflow/profile');
$userModel = Mage::getModel('admin/user');
$userModel->setUserId(0);
Mage::getSingleton('admin/session')->setUser($userModel);
$profile->load($profileId);
if (!$profile->getId()) {
Mage::getSingleton('adminhtml/session')->addError('ERROR: Incorrect profile id');
}

Mage::register('current_convert_profile', $profile);
$profile->run();

echo "EXPORT DISABLED PRODUCTS COMPLETE.\n";
?>

