<?php
class Magestore_Onestepcheckout_Adminhtml_OnestepcheckoutController extends Mage_Adminhtml_Controller_Action
{
     /**
     * Index page
     */
    public function indexAction()
    {
		$this->loadLayout();
        $this->renderLayout();
	}	

	public function gotoonestepcheckoutAction()
	{
		$storeId = $this->getRequest()->getParam('onestepcheckout_storeid');
		$adminId = Mage::getModel('admin/session')->getUser()->getId();
		$key = Mage::getModel('adminhtml/session')->getSessionId();
		$adminLogin = $this->getUrl('onestepcheckoutadmin/adminhtml_onestepcheckout/index');
		$adminLogout = Mage::getBlockSingleton('adminhtml/page_header')->getLogoutLink();
		$random = md5(now());
		$cookieTime = Mage::getStoreConfig('web/cookie/cookie_lifetime');
		$code = md5($key.$random);
		$cookie = Mage::getSingleton('core/cookie');
		$cookie->setLifeTime($cookieTime);
		$cookie->set('onestepcheckout_admin_key', $key);
		$cookie->set('onestepcheckout_admin_code', $code);
		$cookie->set('onestepcheckout_admin_id', $adminId);
		$cookie->set('onestepcheckout_admin_adminlogout', $adminLogout);
		$cookie->set('onestepcheckout_admin_adminlogin', $adminLogin);
		$onestepcheckoutAdmins = Mage::getModel('onestepcheckout/admin')->getCollection()
									->addFieldToFilter('key',$key)
									;
		$onestepcheckoutAdmin = Mage::getModel('onestepcheckout/admin')->load($key,'key');
		if($onestepcheckoutAdmin->getId()){
			$onestepcheckoutAdmin->delete();
		}
		try{
			Mage::getModel('onestepcheckout/admin')
				->setData('key',$key)
				->setData('random',$random)
				->save();
		}catch(Exception $e){	
		}
		$urlRedirect = Mage::getModel('core/store')->load($storeId)->getUrl('onestepcheckout/admin/index');
		// header('Location:'.Mage::getUrl('onestepcheckout/admin/index'));
		header('Location:'.$urlRedirect);
		exit();
	}
	public function index1Action()
    {
		 $this->loadLayout();

        $this->_setActiveMenu('sales/order')
            ->renderLayout();
		// die('123');
		$storeBlock = Mage::getBlockSingleton('adminhtml/sales_order_create_store_select');
		$websiteCollection = $storeBlock->getWebsiteCollection();
		foreach($websiteCollection as $_website){
			$showWebsite=false;
			foreach ($storeBlock->getGroupCollection($_website) as $_group){
				$showGroup=false;
				foreach ($storeBlock->getStoreCollection($_group) as $_store){
					if($showWebsite == false){
						$showWebsite = true;
						echo '<h3>'.$storeBlock->escapeHtml($_website->getName()).'</h3>';
					}
					if($showGroup == false){
						$showGroup = true;
						echo '<h4 style="margin-left:12px;">'.$storeBlock->escapeHtml($_group->getName()).'</h4>';
					}
					echo '<span class="field-row" style="margin-left:28px;">';
					echo '<input type="radio" id="store_'.$_store->getId() .'" class="radio" onclick="order.setStoreId('.$_store->getId().')"/>';
					echo '<label for="store_'.$_store->getId().'" class="inline">'.$storeBlock->escapeHtml($_store->getName()).'</label>';
					echo '</span>';
				}
				if ($showGroup){
				}
			}
		}
		die();
		$adminId = Mage::getModel('admin/session')->getUser()->getId();
		$key = Mage::getModel('adminhtml/session')->getSessionId();
		$adminLogin = $this->getUrl('onestepcheckoutadmin/adminhtml_onestepcheckout/index');
		$adminLogout = Mage::getBlockSingleton('adminhtml/page_header')->getLogoutLink();
		$random = md5(now());
		$cookieTime = Mage::getStoreConfig('web/cookie/cookie_lifetime');
		$code = md5($key.$random);
		$cookie = Mage::getSingleton('core/cookie');
		$cookie->setLifeTime($cookieTime);
		$cookie->set('onestepcheckout_admin_key', $key);
		$cookie->set('onestepcheckout_admin_code', $code);
		$cookie->set('onestepcheckout_admin_id', $adminId);
		$cookie->set('onestepcheckout_admin_adminlogout', $adminLogout);
		$cookie->set('onestepcheckout_admin_adminlogin', $adminLogin);
		$onestepcheckoutAdmins = Mage::getModel('onestepcheckout/admin')->getCollection()
									->addFieldToFilter('key',$key)
									;
		$onestepcheckoutAdmin = Mage::getModel('onestepcheckout/admin')->load($key,'key');
		if($onestepcheckoutAdmin->getId()){
			$onestepcheckoutAdmin->delete();
		}
		try{
			Mage::getModel('onestepcheckout/admin')
				->setData('key',$key)
				->setData('random',$random)
				->save();
		}catch(Exception $e){	
		}
		header('Location:'.Mage::getUrl('onestepcheckout/admin/index'));
		exit();
    }
}
