<?php
class Magestore_Onestepcheckout_AdminController extends Mage_Core_Controller_Front_Action {
	
	//check if email is registered
	private function _emailIsRegistered($email_address) {
		$model = Mage::getModel('customer/customer');
    $model->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email_address);
		if ($model->getId()) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function getOnepage() {
		return Mage::getSingleton('checkout/type_onepage');
	}
	
	public function getSession() {
		return Mage::getSingleton('checkout/session');
	}
	
	/*
	* check if an email is valid
	*/
	public function is_valid_emailAction() {
		$validator = new Zend_Validate_EmailAddress();
		$email_address = $this->getRequest()->getPost('email_address');
		$message = 'Invalid';
		if ($email_address != '') {
			// Check if email is in valid format
			if(!$validator->isValid($email_address)) {
				$message = 'invalid';
			}
			else {
				//if email is valid, check if this email is registered
				if($this->_emailIsRegistered($email_address)) {
					$message = 'exists';
				}
				else {
					$message = 'valid';
				}
			}
		}
		$result = array('message' => $message);
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}
	
	public function show_loginAction() {
		$this->loadLayout();     		
		$this->renderLayout();		
	}
	
	public function show_passwordAction() {
		$this->loadLayout();     		
		$this->renderLayout();		
	}
	
	/*
	* send new password to customer 
	*/
	public function retrievePasswordAction() {
		$email = $this->getRequest()->getPost('email', false);
		$result = array();
		if ($email) {
			$customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email); 
			if ($customer->getId()) {
				try {
					$newPassword = $customer->generatePassword();
					$customer->changePassword($newPassword, false);
					$customer->sendPasswordReminderEmail();
					$result = array('success' => true);
				}
				catch (Exception $e){
					$result = array('success' => false, 'error' => $e->getMessage());
				}
			}
			else {
				$result = array('success' => false, 'error' => 'This email address was not found in our records.');
			}
		}		
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}
	
	/*
	* show term and condition pop up
	*/
	public function show_term_conditionAction() {
		$helper = Mage::helper('onestepcheckout');
		if ($helper->enableTermsAndConditions()) {
			$html = $helper->getTermsConditionsHtml();
			echo $html;
			echo '<p class="a-right"><a href="#" onclick="javascript:TINY.box.hide();return false;">Close</a></p>';
		}
	}
	
	/*
	* add coupon to the order
	* copy from CartController.php
	*/
	public function add_couponAction() {
		$couponCode = (string)$this->getRequest()->getPost('coupon_code', '');
				
		$quote = $this->getOnepage()->getQuote();
		if ($this->getRequest()->getParam('remove') == '1') {
			$couponCode = '';
		}
		
		$oldCouponCode = $quote->getCouponCode();
		if (!strlen($couponCode) && !strlen($oldCouponCode)) {
			return;
		}
		try {
			$error = false;
			$quote->getShippingAddress()->setCollectShippingRates(true);
			$quote->setCouponCode(strlen($couponCode) ? $couponCode : '')
                ->collectTotals()
                ->save();
			if ($couponCode) {
				if ($couponCode == $quote->getCouponCode()) {
					$message = $this->__('Coupon code "%s" was applied.', Mage::helper('core')->htmlEscape($couponCode));
				}
				else {
					$error = true;
					$message = $this->__('Coupon code "%s" is not valid.', Mage::helper('core')->htmlEscape($couponCode));
				}
			} else {
				$message = $this->__('Coupon code was canceled.');
			}
		}
		catch(Mage_Core_Exception $e) {
			$error = true;
			$message = $e->getMessage();
		}
		catch (Exception $e) {
			$error = true;
			$message = $this->__('Cannot apply the coupon code.');
		}
		//reload HTML for review order section
		$reviewHtml = $this->_getReviewTotalHtml();		
		$result = array(
			'error' => $error,
			'message' => $message,
			'review_html' => $reviewHtml
		);
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}
	
	/*
	* process login action in check out.
	*/
	public function loginPostAction() {
		$email = $this->getRequest()->getPost('email', false);
		$password = $this->getRequest()->getPost('password', false);
		
		$error = '';
		if ($email && $password) {
			try {
				$this->_getCustomerSession()->login($email, $password);
			}
			catch(Exception $ex) {
				$error = $ex->getMessage();
			}
		}
		$result = array();
		$result['error'] = $error;
		if ($error == '') $result['success'] = true;
		$this->getResponse()->setBody(Zend_Json::encode($result));
	}
	
	/*
	* save billing & shipping address
	*/
	public function save_addressAction() {
		$billing_data = $this->getRequest()->getPost('billing', false);
		$shipping_data = $this->getRequest()->getPost('shipping', false);
		$shipping_method = $this->getRequest()->getPost('shipping_method', false);
		$billing_address_id = $this->getRequest()->getPost('billing_address_id', false);	
		
		//load default data for disabled fields
		if (Mage::helper('onestepcheckout')->isUseDefaultDataforDisabledFields()) {
			Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($billing_data);
			Mage::helper('onestepcheckout')->setDefaultDataforDisabledFields($shipping_data);
		}
		
		if (isset($billing_data['use_for_shipping']) && $billing_data['use_for_shipping'] == '1') {
			$shipping_address_data = $billing_data;
		}
		else {
			$shipping_address_data = $shipping_data;
		}
		
		$billing_street = trim(implode("\n", $billing_data['street']));
		$shipping_street = trim(implode("\n", $shipping_address_data['street']));
		
		if(isset($billing_data['email'])) {
			$billing_data['email'] = trim($billing_data['email']);
		}								
		
		// Ignore disable fields validation --- Only for 1..4.1.1
		$this->setIgnoreValidation();
		if(Mage::helper('onestepcheckout')->isShowShippingAddress()) {
			if(!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1')	{		
				$shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
				$this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
			}
		}		
		$this->getOnepage()->saveBilling($billing_data, $billing_address_id);
		if($billing_data['country_id']){
			Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id',$billing_data['country_id'])->save();
		}
		//var_dump($billing_data['country_id']);die();
		//Mage::getModel('core/session')->setData('country',$billing_data['country_id']);
		// if different shipping address is enabled and customer ship to another address, save it
		
		
		if ($shipping_method && $shipping_method != '') {
			Mage::helper('onestepcheckout')->saveShippingMethod($shipping_method);
		}
		$this->loadLayout(false);
		$this->renderLayout();	
	}
	
	/*
	* save shipping & payment method 
	*/
	public function save_shippingAction() {
		$shipping_method = $this->getRequest()->getPost('shipping_method', '');
		$payment_method = $this->getRequest()->getPost('payment_method', false);
		$old_shipping_method = $this->getOnepage()->getQuote()->getShippingAddress()->getShippingMethod();
		$billing_data = $this->getRequest()->getPost('billing', false);
		if($billing_data['country_id']){
			Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id',$billing_data['country_id'])->save();
		}
		// if ($shipping_method && $shipping_method != '' && $shipping_method != $old_shipping_method) {
			Mage::helper('onestepcheckout')->saveShippingMethod($shipping_method);
			$this->getOnepage()->saveShippingMethod($shipping_method);
		// }
		
		// if ($payment_method != '') {
			try{
				$payment = $this->getRequest()->getPost('payment', array());
				$payment['method'] = $payment_method;
				$this->getOnepage()->savePayment($payment);
				Mage::helper('onestepcheckout')->savePaymentMethod($payment);
			}
			catch(Exception $e) {
				//
			}
		// }
		
		// $paymentHtml = $this->_getPaymentMethodsHtml();
		// $reviewHtml = $this->_getReviewTotalHtml();
		// $result = array('payment' => $paymentHtml, 
										// 'review' => $reviewHtml);
		// $this->getResponse()->setBody(Zend_Json::encode($result));
		$this->loadLayout(false);
		$this->renderLayout();
	}
	
	public function saveOrderAction() {
		$post = $this->getRequest()->getPost();
		if (!$post) return;
		$error = false;
		$helper = Mage::helper('onestepcheckout');
		
		$billing_data = $this->getRequest()->getPost('billing', array());
		$shipping_data = $this->getRequest()->getPost('shipping', array());
		$shippingDescription = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingDescription();

		$billing_data['save_in_address_book'] = '';
		/*Login for existing customer*/
		if($customer_id = $billing_data['customer_id']){
			// Mage::getModel('customer/session')->setCustomerAsLoggedIn(Mage::getModel('customer/customer')->load($customer_id));
		}elseif($billing_data['account_type']=='exist'){
			$email_address = $billing_data['email'];
			if($this->_emailIsRegistered($email_address)){
				
				$customerExist = Mage::getModel('customer/customer')
									->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email_address);
				Mage::getModel('customer/session')->setCustomerAsLoggedIn($customerExist);
			}
		}
		//set checkout method 
		$checkoutMethod = '';
		if (!$this->_isLoggedIn() && (!isset($billing_data['customer_id']) || $billing_data['customer_id'] == '')) {
			$checkoutMethod = 'guest';
			// if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
				$is_create_account = $this->getRequest()->getPost('create_account_checkbox');
				$email_address = $billing_data['email'];
				if ($is_create_account || !$helper->allowGuestCheckout()) {
					if ($this->_emailIsRegistered($email_address)) {						
						$error = true;
						Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
						$this->_redirect('*/admin/index');

					}else {
						if (!$billing_data['customer_password'] || !$billing_data['customer_password'] == '' 

							|| !$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {

								$billing_data['customer_password'] = Mage::helper('core')->uniqHash();
								$billing_data['confirm_password'] = $billing_data['customer_password'];

						}
						$checkoutMethod = 'register';					
					}
				}

			// }
		}
		if ($checkoutMethod != '') $this->getOnepage()->saveCheckoutMethod($checkoutMethod);
		
		//to ignore validation for disabled fields
		$this->setIgnoreValidation();
		
		//resave billing address to make sure there is no error if customer change something in billing section before finishing order
		$customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
		$result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
		if(isset($result['error']))	{
			$error = true;
			if (is_array($result['message']) && isset($result['message'][0]))
				Mage::getSingleton('checkout/session')->addError($result['message'][0]);
			else 
				Mage::getSingleton('checkout/session')->addError($result['message']);
			$this->_redirect('*/admin/index');			
		}
		
		//re-save shipping address
		$shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
		if($helper->isShowShippingAddress()) {
			if(!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1')	{				
				$result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
				if(isset($result['error'])) {
					$error = true;
					if (is_array($result['message']) && isset($result['message'][0]))
						Mage::getSingleton('checkout/session')->addError($result['message'][0]);
					else 
						Mage::getSingleton('checkout/session')->addError($result['message']);
					$this->_redirect('*/admin/index');
				}
			}
			else {
				$result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
				// $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id); 
			}
		}
		
		//re-save shipping method
		$shipping_method = $this->getRequest()->getPost('shipping_method', '');
		if(!$this->isVirtual()) {			
			$result = $this->getOnepage()->saveShippingMethod($shipping_method);
			
			if(isset($result['error'])) {
				$error = true;
				if (is_array($result['message']) && isset($result['message'][0]))	{					
					Mage::getSingleton('checkout/session')->addError($result['message'][0]);
				}
				else {					
					Mage::getSingleton('checkout/session')->addError($result['message']);
				}
				$this->_redirect('*/admin/index');
			}
			else {
				Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));				
			}		
		}
		$paymentRedirect = false;
		//save payment method		
		try {
			$result = array();
			$payment = $this->getRequest()->getPost('payment', array());	
			$result = $helper->savePaymentMethod($payment);
			if($payment){
				$this->getOnepage()->getQuote()->getPayment()->importData($payment);
			}
			$paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
		}
		catch (Mage_Payment_Exception $e) {
			if ($e->getFields()) {
				$result['fields'] = $e->getFields();
			}
			$result['error'] = $e->getMessage();			
		} catch (Mage_Core_Exception $e) {
			$result['error'] = $e->getMessage();
		} catch (Exception $e) {
			Mage::logException($e);
			$result['error'] = $this->__('Unable to set Payment Method.');
		}
		
		if (isset($result['error'])) {
			$error = true;
			Mage::getSingleton('checkout/session')->addError($result['error']);
			$this->_redirect('*/admin/index');
		}
		
		if ($paymentRedirect && $paymentRedirect != '') {
			Header('Location: ' . $paymentRedirect);
			exit();
		}
		
		//only continue to process order if there is no error
		if (!$error) {
			//newsletter subscribe
			if ($helper->isShowNewsletter()) {
				$news_billing = $this->getRequest()->getPost('billing');		
				// $is_subscriber = $this->getRequest()->getPost('newsletter_subscriber_checkbox', false);	
				$is_subscriber = $news_billing['newsletter_subscriber_checkbox'];					
				if ($is_subscriber) {
					$subscribe_email = '';
					//pull subscriber email from billing data
					if (isset($billing_data['email']) && $billing_data['email'] != '') {
						$subscribe_email = $billing_data['email'];
					}				
					else if ($this->_isLoggedIn()) {
						$subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
					}
					//check if email is already subscribed
					$subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
					if ($subscriberModel->getId() === NULL) { 
						Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);					
					}else if($subscriberModel->getData('subscriber_status') !=1 ){
						$subscriberModel->setData('subscriber_status', 1);
						try{
							$subscriberModel->save();
						}catch(Exception $e){
						}
					}
				}
			}			
			
			try {
				$result = $this->getOnepage()->saveOrder();
				$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
			}
			catch (Mage_Core_Exception $e) {
				Mage::logException($e);
				Mage::getSingleton('checkout/session')->addError($e->getMessage());
				Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
				$redirect = Mage::getUrl('onestepcheckout/admin/index');
				Header('Location: ' . $redirect);
				exit();
			}
			catch (Exception $e) {
				Mage::logException($e);
				Mage::getSingleton('checkout/session')->addError($e->getMessage());
				Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
				$redirect = Mage::getUrl('onestepcheckout/admin/index');
				Header('Location: ' . $redirect);
				exit();
			}
			$this->getOnepage()->getQuote()->save();
			if($redirectUrl)    {
				$redirect = $redirectUrl;
			}
			else {
				Mage::getSingleton('core/session')->addSuccess(Mage::helper('onestepcheckout')->__('Checkout Success!'));
				$lastOrderId = Mage::getSingleton('checkout/type_onepage')->getCheckout()->getLastOrderId();
				$order = Mage::getModel('sales/order')->load($lastOrderId);
				$_order_items = $order->getAllItems(); 
				if(isset($billing_data['customer_id']) && $billing_data['customer_id'] != ''){			
					$order->setCustomerId($billing_data['customer_id']);
				}
				$savedQtys = array();
				foreach($_order_items as $_order_item){
					$savedQtys[$_order_item->getId()] = $_order_item->getQtyOrdered();
				} 
				$totalPaid = $this->getRequest()->getPost('cash-in');
				$totalRefunded = $this->getRequest()->getPost('balance');
				$transaction = Mage::getModel('core/resource_transaction')
                    ->addObject($order);
				if($totalPaid >= $order->getGrandTotal()){
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($savedQtys);
					$invoice->register();
                    $transaction->addObject($invoice);
				}
				$shipped = $billing_data['onestepcheckout_shipped'];
				if($shipped){
					$shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($savedQtys);
					$shipment->register();
                    $transaction->addObject($shipment);
				}
				try{
					if($shipped || ($totalPaid >= $order->getGrandTotal()))
						$transaction->save();
				}catch(Exception $e){
				}
				if($totalRefunded <=0 ){
					$totalRefunded = 0;
				}
				try{
					if($totalPaid < $order->getGrandTotal() && !$shipped){
						$order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
					}
					if(($totalPaid < $order->getGrandTotal() && $shipped)||($totalPaid >= $order->getGrandTotal() && !$shipped)){
						$order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
					}
			
					$cookie = Mage::getSingleton('core/cookie');
					$adminId = $cookie->get('onestepcheckout_admin_id');
					if($adminId){
						$adminName = Mage::getModel('admin/user')->load($adminId)->getUsername();
						$order->setOnestepcheckoutAdminId($adminId)
							  ->setOnestepcheckoutAdminName($adminName);
					}
					if(!$order->getData('shipping_description') && $shippingDescription)
						$order->setData('shipping_description',$shippingDescription);
					$order->setTotalPaid($totalPaid)
						  ->setBaseTotalPaid($totalPaid)
						  ->setTotalRefunded($totalRefunded)
						  ->setBaseTotalRefunded($totalRefunded)
						  ->save();
				}catch(Exception $e){
				}
				$cookie = Mage::getSingleton('core/cookie');
				$cookieTime = Mage::getStoreConfig('web/cookie/cookie_lifetime');
				$cookie->setLifeTime($cookieTime);
				$cookie->set('onestepcheckout_order_id', $lastOrderId);
				$redirect = Mage::getUrl('onestepcheckout/admin/index');			
				Mage::getModel('core/session')->setData('onestepcheckout_order_id',$order->getId());
			}
			Header('Location: ' . $redirect);
			exit();	
			
		}
		else {
			$this->_redirect('*/admin/index');
		}
		
	}
	
	
	
	public function saveOrderProAction() {
		$post = $this->getRequest()->getPost();
		$result = new stdClass();
		if (!$post) return;
		$error = false;
		$helper = Mage::helper('onestepcheckout');
		
		$billing_data = $this->getRequest()->getPost('billing', array());
		$shipping_data = $this->getRequest()->getPost('shipping', array());
		
		//set checkout method 
		$checkoutMethod = '';
		if (!$this->_isLoggedIn()) {
			$checkoutMethod = 'guest';
			if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
				$is_create_account = $this->getRequest()->getPost('create_account_checkbox');
				$email_address = $billing_data['email'];
				if ($is_create_account || !$helper->allowGuestCheckout()) {
					if ($this->_emailIsRegistered($email_address)) {						
						$error = true;
						Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
						$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
					}
					else {
						if (!$billing_data['customer_password'] || $billing_data['customer_password'] == '') {
							$error = true;							
						}
						else if (!$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {
							$error = true;
						}
						else if ($billing_data['confirm_password'] !== $billing_data['customer_password']) {
							$error = true;
						}
						if ($error) {
							Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please correct your password.'));
							$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
							$result->url = $redirect;
							$this->getResponse()->setBody(json_encode($result)); 
						}
						else {
							$checkoutMethod = 'register';					
						}
					}
				}
			}
		}
		if ($checkoutMethod != '') $this->getOnepage()->saveCheckoutMethod($checkoutMethod);
		
		//to ignore validation for disabled fields
		$this->setIgnoreValidation();
		
		//resave billing address to make sure there is no error if customer change something in billing section before finishing order
		$customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
		$result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
		if(isset($result['error']))	{
			$error = true;
			if (is_array($result['message']) && isset($result['message'][0]))
				Mage::getSingleton('checkout/session')->addError($result['message'][0]);
			else 
				Mage::getSingleton('checkout/session')->addError($result['message']);
		$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 		
		}
		
		//re-save shipping address
		$shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
		if($helper->isShowShippingAddress()) {
			if(!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1')	{				
				$result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
				if(isset($result['error'])) {
					$error = true;
					if (is_array($result['message']) && isset($result['message'][0]))
						Mage::getSingleton('checkout/session')->addError($result['message'][0]);
					else 
						Mage::getSingleton('checkout/session')->addError($result['message']);
					$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
				}
			}
			else {
				$result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
				// $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id); 
			}
		}
		
		//re-save shipping method
		$shipping_method = $this->getRequest()->getPost('shipping_method', '');
		if(!$this->isVirtual()) {			
			$result = $this->getOnepage()->saveShippingMethod($shipping_method);
			if(isset($result['error'])) {
				$error = true;
				if (is_array($result['message']) && isset($result['message'][0]))	{					
					Mage::getSingleton('checkout/session')->addError($result['message'][0]);
				}
				else {					
					Mage::getSingleton('checkout/session')->addError($result['message']);
				}
				$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
			}
			else {
				Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request'=>$this->getRequest(), 'quote'=>$this->getOnepage()->getQuote()));				
			}		
		}
		
		$paymentRedirect = false;
		//save payment method		
		try {
			$result = array();
			$payment_method = $this->getRequest()->getPost('payment', array());
			$payment['method'] = $payment_method;
			$result = $helper->savePaymentMethod($payment);
			if($payment){
				$this->getOnepage()->getQuote()->getPayment()->importData($payment);
			}
			$paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();

		}
		catch (Mage_Payment_Exception $e) {

			if ($e->getFields()) {
				$result['fields'] = $e->getFields();
			}
			$result['error'] = $e->getMessage();			
		} catch (Mage_Core_Exception $e) {

			$result['error'] = $e->getMessage();
		} catch (Exception $e) {

			Mage::logException($e);
			$result['error'] = $this->__('Unable to set Payment Method.');
		}
		
		if (isset($result['error'])) {
			$error = true;
			Mage::getSingleton('checkout/session')->addError($result['error']);
			$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
		}
		
		if ($paymentRedirect && $paymentRedirect != '') {
			
			// Zend_debu
			$result = new stdClass();
			$result->url = $paymentRedirect;
				$this->getResponse()->setBody(json_encode($result)); 
				
		}
		else{
		
		//only continue to process order if there is no error
			if (!$error) {
				//newsletter subscribe
				if ($helper->isShowNewsletter()) {
					$news_billing = $this->getRequest()->getPost('billing');							
					$is_subscriber = $news_billing['newsletter_subscriber_checkbox'];	
					// var_dump($is_subscriber);die();
					if ($is_subscriber) {
						$subscribe_email = '';
						//pull subscriber email from billing data
						if (isset($billing_data['email']) && $billing_data['email'] != '') {
							$subscribe_email = $billing_data['email'];
						}				
						else if ($this->_isLoggedIn()) {
							$subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
						}
						//check if email is already subscribed
						$subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
						if ($subscriberModel->getId() === NULL) { 
							Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);					
						}else if($subscriberModel->getData('subscriber_status') !=1 ){
							$subscriberModel->setData('subscriber_status', 1);
							try{
								$subscriberModel->save();
							}catch(Exception $e){
							}
						}
					}
				}			
				
				try {
					$result = $this->getOnepage()->saveOrder();
					$redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
				}
				catch (Mage_Core_Exception $e) {
					Mage::logException($e);
					Mage::getSingleton('checkout/session')->addError($e->getMessage());
					Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
					$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
				}
				catch (Exception $e) {
					Mage::logException($e);
					Mage::getSingleton('checkout/session')->addError($e->getMessage());
					Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
					$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
				}
				$this->getOnepage()->getQuote()->save();
				
				if($payment['method']=='hosted_pro'){
				$this->loadLayout('checkout_onepage_review');
					$html = $this->getLayout()->getBlock('paypal.iframe')->toHtml();
					
					$result->html = $html;
					$result->url = 'null';
				
					$this->getResponse()->setBody(json_encode($result));  
				}
				else{
					if($redirectUrl)    {
						$redirect = $redirectUrl;
					}  
					else {
						$redirect = Mage::getUrl('checkout/onepage/success');			
					}
					$result->html = '';					
					$result->url = $redirect;
					
					$this->getResponse()->setBody(json_encode($result));  
	
				}
			}
			else {
			$result = new stdClass();
				$redirect = Mage::getUrl('onestepcheckout/admin/index');
					// Header('Location: ' . $redirect);
					// exit();
					$result->url = $redirect;
					$this->getResponse()->setBody(json_encode($result)); 
			}
		}
		
	}
	
	protected function _getCustomerSession() {
		return Mage::getSingleton('customer/session');
	}
	
	/*
	* Reload shipping method html
	*/
	protected function _getShippingMethodsHtml() {		
		//$this->_cleanLayoutCache();
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
		$update->load('onestepcheckout_onestepcheckout_shippingmethod');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getOutput();
		return $output;
	}
	
	/*
	* Reload payment method html
	*/
	public function _getPaymentMethodsHtml() {
		//$this->_cleanLayoutCache();
		$layout = $this->getLayout();		
		$update = $layout->getUpdate();
		$update->load('onestepcheckout_onestepcheckout_paymentmethod');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getOutput();
		return $output;
	}
	
	public function _getReviewTotalHtml() {
		//$this->_cleanLayoutCache();
		$layout = $this->getLayout();
		$update = $layout->getUpdate();
		$update->load('onestepcheckout_onestepcheckout_review');
		$layout->unsetBlock('shippingmethod');
		$layout->generateXml();
		$layout->generateBlocks();
		$output = $layout->getOutput();
		return $output;
	}
	
	protected function _isLoggedIn() {
		return $this->_getCustomerSession()->isLoggedIn();
	}
	
	public function isVirtual() {
		return $this->getOnepage()->getQuote()->isVirtual();
	}
	
	/*
	* this function is to pass the validation 
	* Only available for Magento 1.4.x 
	*/
	public function setIgnoreValidation() {
		$this->getOnepage()->getQuote()->getBillingAddress()->setShouldIgnoreValidation(true);
		$this->getOnepage()->getQuote()->getShippingAddress()->setShouldIgnoreValidation(true);
	}
	
	protected function _cleanLayoutCache() {
		Mage::app()->cleanCache(LAYOUT_GENERAL_CACHE_TAG);
	}
	
	public function enableCustomerFields()
	{
		$helper = Mage::helper('onestepcheckout');
		$fieldValue = $helper->getFieldValue();
		$prefix = 0;
		$suffix = 0;
		$middlename = 0;
		$birthday = 0;
		$gender = 0;
		$taxvat = 0;
		for($i=0;$i<20;$i++){
			if($helper->getFieldEnable($i)=='prefix') $prefix = 1;
			if($helper->getFieldEnable($i)=='suffix') $suffix = 1;
			if($helper->getFieldEnable($i)=='middlename') $middlename = 1;
			if($helper->getFieldEnable($i)=='birthday') $birthday = 1;
			if($helper->getFieldEnable($i)=='gender') $gender = 1;
			if($helper->getFieldEnable($i)=='taxvat') $taxvat = 1;
		}
		try{
			if($prefix==1){
				//if(!Mage::getStoreConfig('customer/address/prefix_show')=='0' || Mage::getStoreConfig('customer/address/prefix_show')=='0'){
					if($helper->getFieldRequire('prefix')){
						Mage::getConfig()->saveConfig('customer/address/prefix_show','req');
						$this->updateAttribute('prefix','reg');
					}else{
						Mage::getConfig()->saveConfig('customer/address/prefix_show','opt');	
						$this->updateAttribute('prefix','opt');
					}
				//}
			}
			if($suffix==1){
				//if(!Mage::getStoreConfig('customer/address/suffix_show')=='0' || Mage::getStoreConfig('customer/address/suffix_show')=='0'){
					if($helper->getFieldRequire('suffix')){
						Mage::getConfig()->saveConfig('customer/address/suffix_show','req');
						$this->updateAttribute('suffix','req');
					}else{
						Mage::getConfig()->saveConfig('customer/address/suffix_show','opt');	
						$this->updateAttribute('suffix','opt');
					}
				//}
			}
			if($middlename==1){
				//if(!Mage::getStoreConfig('customer/address/middlename_show')=='0' || Mage::getStoreConfig('customer/address/middlename_show')=='0'){
					Mage::getConfig()->saveConfig('customer/address/middlename_show','1');
					$this->updateAttribute('middlename','1');
				//}
			}
			if($birthday==1){
				//if(!Mage::getStoreConfig('customer/address/dob_show')=='0' || Mage::getStoreConfig('customer/address/dob_show')=='0'){
					if($helper->getFieldRequire('birthday')){
						Mage::getConfig()->saveConfig('customer/address/dob_show','req');
						$this->updateAttribute('dob','req');
					}else{
						Mage::getConfig()->saveConfig('customer/address/dob_show','opt');	
						$this->updateAttribute('dob','opt');
					}
				//}
			}
			if($gender==1){
				//if(!Mage::getStoreConfig('customer/address/gender_show')=='0' || Mage::getStoreConfig('customer/address/gender_show')=='0'){
					if($helper->getFieldRequire('gender')){
						Mage::getConfig()->saveConfig('customer/address/gender_show','req');
						$this->updateAttribute('gender','req');
					}else{
						Mage::getConfig()->saveConfig('customer/address/gender_show','opt');	
						$this->updateAttribute('gender','opt');
					}
				//}
			}
			if($taxvat==1){
				//if(!Mage::getStoreConfig('customer/address/taxvat_show')=='0' || Mage::getStoreConfig('customer/address/taxvat_show')=='0'){
					if($helper->getFieldRequire('taxvat')){
						Mage::getConfig()->saveConfig('customer/address/taxvat_show','req');
						$this->updateAttribute('taxvat','req');
					}else{
						Mage::getConfig()->saveConfig('customer/address/taxvat_show','opt');	
						$this->updateAttribute('taxvat','opt');
					}
				//}
			}
		}catch(Exception $e){
		}
	}
	public function updateAttribute($attribute,$option)
	{
		$attributeObject = Mage::getSingleton('eav/config')->getAttribute('customer', $attribute);
		 $valueConfig = array(
            ''    => array('is_required' => 0, 'is_visible' => 0),
            'opt' => array('is_required' => 0, 'is_visible' => 1),
            '1'   => array('is_required' => 0, 'is_visible' => 1),
            'req' => array('is_required' => 1, 'is_visible' => 1),
        );
		$data = $valueConfig[$option];
		$attributeObject->setData('is_required', $data['is_required']);
		$attributeObject->setData('is_visible',  $data['is_visible']);
		$attributeObject->save();
	}
	
	
	/*Onestepcheckout for Admin*/
	public function indexAction() {
		if(!Mage::helper('magenotification')->checkLicenseKey('Onestepcheckout')){
			return $this->_redirect('adminhtml/dashboard/index');
		}
		if(Mage::getSingleton('customer/session')->isLoggedIn()){
			Mage::getSingleton('customer/session')->logout();
			return $this->_redirect('onestepcheckout/admin/index');
		}
		$this->enableCustomerFields();
		$cookie = Mage::getSingleton('core/cookie');
		$key = $cookie->get('onestepcheckout_admin_key');
		$code = $cookie->get('onestepcheckout_admin_code');
		$adminId = $cookie->get('onestepcheckout_admin_id');
		$adminLogin = $cookie->get('onestepcheckout_admin_adminlogin');
		Mage::getModel('core/session')->setData('onestepcheckout_admin_adminlogin',$adminLogin);
		$adminLogout = $cookie->get('onestepcheckout_admin_adminlogout');
		if($key){
			$onestepcheckoutAdmin = Mage::getModel('onestepcheckout/admin')->load($key,'key');
			if($onestepcheckoutAdmin->getId()){
				$random = $onestepcheckoutAdmin->getData('random');
				$codeCheck = md5($key.$random);
				if($codeCheck == $code){
					$cookieTime = Mage::getStoreConfig('web/cookie/cookie_lifetime');
					$cookie->setLifeTime($cookieTime);
					$cookie->set('onestepcheckout_admin_key', $key);
					$cookie->set('onestepcheckout_admin_code', $code);
					$cookie->set('onestepcheckout_admin_id', $adminId);
					$cookie->set('onestepcheckout_admin_adminlogout', $adminLogout);
					$cookie->set('onestepcheckout_admin_adminlogin', $adminLogin);
				}else{
					if(Mage::getModel('core/session')->getData('onestepcheckout_admin_adminlogin')){
						header('Location:'.Mage::getModel('core/session')->getData('onestepcheckout_admin_adminlogin'));
						exit();
					}else{
						return $this->_redirect('adminhtml/dashboard/index');
					}
				}
			}else{
				if(Mage::getModel('core/session')->getData('onestepcheckout_admin_adminlogin')){
					header('Location:'.Mage::getModel('core/session')->getData('onestepcheckout_admin_adminlogin'));
					exit();
				}else{
					return $this->_redirect('adminhtml/dashboard/index');
				}
			}
		}else{
			if(Mage::getModel('core/session')->getData('onestepcheckout_admin_adminlogin')){
				header('Location:'.Mage::getModel('core/session')->getData('onestepcheckout_admin_adminlogin'));
				exit();
			}else{
				return $this->_redirect('adminhtml/dashboard/index');
			}
		}
		$quote = $this->getOnepage()->getQuote();
	
		if(!Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->getData('country_id')){
			if(Mage::getStoreConfig('onestepcheckout/general/country_id')){
				Mage::getModel('checkout/session')->getQuote()->getBillingAddress()->setData('country_id',Mage::getStoreConfig('onestepcheckout/general/country_id'))->save();
			}
		}
		$checkNull = 1;
		$helper = Mage::helper('onestepcheckout');
		for($i=0;$i<15;$i++){
			if($helper->getFieldEnable($i)){
				$checkNull = 0;
				break;
			}
		}
		if($checkNull==1){
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_0','firstname');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_1','lastname');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_2','email');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_3','telephone');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_4','street');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_6','country');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_8','city');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_10','postcode');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_11','region');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_12','company');
			Mage::getConfig()->saveConfig('onestepcheckout/field_position_management/row_13','fax');
		}
		$this->loadLayout();
		$this->_initLayoutMessages('checkout/session');
		$this->getLayout()->getBlock('head')->setTitle($this->__('One Step Checkout For Admin'));
		$this->renderLayout();
	}
	
	public function productSearchAction()
	{	
		$storeId = Mage::app()->getStore()->getStoreId();
		$keyword = $this->getRequest()->getPost('keyword');
		$productBlock = Mage::getBlockSingleton('catalog/product_list');
		$result = array();				
		
		//search by SKU
		$productSkus = Mage::getModel('catalog/product')->getCollection()	
			->addAttributeToSelect('*')	
			->setStoreId($storeId)
			->addStoreFilter($storeId)
			->addFieldToFilter("status",1)	
			->addFieldToFilter('sku',array('like'=>'%'. $keyword.'%'))	
			->setCurPage(1)
			;
		
		Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($productSkus);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($productSkus);

		//search by name
		$productNames = Mage::getModel('catalog/product')->getCollection()	
			->addAttributeToSelect('*')	
			->setStoreId($storeId)
			->addStoreFilter($storeId)
			->addFieldToFilter("status",1)	
			->addFieldToFilter('name',array('like'=>'%'. $keyword.'%'))	
			->setCurPage(1)
			;
		Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($productNames);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInSiteFilterToCollection($productNames);
		// if(count($productNames))
		// {
			// foreach($productNames as $product)
			// {
				// if(!in_array($product->getId(),$result))
					// $result[] = $product->getId();
			// }
		// }
		$html = '';
		$html .= '<ul>';

		if(count($productSkus)){
			foreach($productSkus as $product){
				if(!in_array($product->getId(),$result)){
					$addToCart = $productBlock->getAddToCartUrl($product);
					$result[] = $product->getId();
					// $html .=
					$html .= '<li onclick="setLocation(\''.$addToCart.'\')">';
					$html .= '<strong>'.Mage::getBlockSingleton('core/template')->htmlEscape($product->getName()).'</strong>-'.Mage::helper('core')->currency($product->getFinalPrice());
					$html .= '<br /><strong>SKU: </strong>'.$product->getSku();
					$html .= '</li>';
				}
			}
		}
		if(count($productNames)){
			foreach($productNames as $product){
				if(!in_array($product->getId(),$result)){
					$addToCart = $productBlock->getAddToCartUrl($product);
					$result[] = $product->getId();
					$html .= '<li onclick="setLocation(\''.$addToCart.'\')">';
					$html .= '<strong>'.Mage::getBlockSingleton('core/template')->htmlEscape($product->getName()).'</strong>-'.Mage::helper('core')->currency($product->getFinalPrice());
					$html .= '<br /><strong>SKU: </strong>'.$product->getSku();
					$html .= '</li>';
				}
			}	
		}	

		//ONLY RESULT
		$productSkuOnly = Mage::getModel('catalog/product')->getCollection()	
			->addAttributeToSelect('*')	
			->setStoreId($storeId)
			->addStoreFilter($storeId)
			->addFieldToFilter("status",1)	
			->addFieldToFilter('sku',$keyword)	
			->setCurPage(1)
			;		
		if(count($productSkuOnly) == 1 && count($productSkus) <=1 && count($productNames) <=1){
			foreach($productSkuOnly as $product){			
				$addToCart = $productBlock->getAddToCartUrl($product);
				$result[] = $product->getId();
				$html = '';
				$html .= '<ul>';
				$html .= '<li id="sku_only" url="'.$addToCart.'" onclick="setLocation(\''.$addToCart.'\')">';
				$html .= '<strong>'.Mage::getBlockSingleton('core/template')->htmlEscape($product->getName()).'</strong>-'.Mage::helper('core')->currency($product->getFinalPrice());
				$html .= '<br /><strong>SKU: </strong>'.$product->getSku();
				$html .= '</li>';
				$html .= '</ul>';	
			}
			echo $html;
			return;
		}		
		
		// $productNameOnly = Mage::getModel('catalog/product')->getCollection()	
			// ->addAttributeToSelect('*')	
			// ->setStoreId($storeId)
			// ->addStoreFilter($storeId)
			// ->addFieldToFilter("status",1)	
			// ->addFieldToFilter('name',array('like'=>$keyword.'%'))	
			// ->setCurPage(1)
			// ;
		// if(count($productNameOnly) == 1){
			// foreach($productNameOnly as $product){				
				// $addToCart = $productBlock->getAddToCartUrl($product);
				// $result[] = $product->getId();
				// $html = '';
				// $html .= '<ul>';
				// $html .= '<li id="sku_only" url="'.$addToCart.'">';
				// $html .= '<strong>'.Mage::getBlockSingleton('core/template')->htmlEscape($product->getName()).'</strong>-'.Mage::helper('core')->currency($product->getFinalPrice());
				// $html .= '<br /><strong>SKU: </strong>'.$product->getSku();
				// $html .= '</li>';
				// $html .= '</ul>';			
			// }
			// echo $html;
			// return;
		// }
		
		$html .= '</ul>';
		if(!count($productSkus) && !count($productNames)){
			$html = '<ul><li>No product</li></ul>';
		}
		echo $html;
	}
	
	public function customerSearchAction()
	{	
		$storeId = Mage::app()->getStore(true)->getId();
		$keyword = $this->getRequest()->getPost('keyword');
		$result = array();
		$websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();
		$customers = Mage::getResourceModel('customer/customer_collection')
				// ->setWebsiteId($website_id)
				->addAttributeToFilter('website_id',$websiteId)
                ->addNameToSelect()
                ->addAttributeToSelect('*')
                ->joinAttribute('telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                ->addAttributeToFilter(array(
                    array('attribute' => 'firstname', 'like' => '%' . $keyword . '%'),
                    array('attribute' => 'lastname', 'like' => '%' . $keyword . '%'),
                    array('attribute' => 'telephone', 'like' => '%' . $keyword . '%'),
                    array('attribute' => 'email', 'like' => '%' . $keyword . '%'),
                ))
                // ->setPage($this->getStart(), $this->getLimit())
                ->load();
		$html = '';
		$html .= '<ul>';
		foreach($customers as $customer){
			// var_dump($customer->getData());
			if($_pAddsses = $customer->getDefaultBilling()){
				$customerAddress = Mage::getModel('customer/address')->load($customer->getDefaultBilling());
				$street = $customerAddress->getStreet();
				if($street[0]){
					$street = $street[0];
				}else{
					$street = '';
				}
				$html .= '<li id="'.$customer->getId().'" email="'.$customer->getEmail().'" prefix="'.$customerAddress->getPrefix().'" firstname="'.$customerAddress->getFirstname().'" middlename="'.$customerAddress->getMiddlename().'"
							lastname="'.$customerAddress->getLastname().'" suffix="'.$customerAddress->getSuffix().'" company="'.$customerAddress->getCompany().'"
							city="'.$customerAddress->getCity().'" country_id="'.$customerAddress->getCountryId().'" region="'.$customerAddress->getRegion().'"
							postcode="'.$customerAddress->getPostcode().'" telephone="'.$customerAddress->getTelephone().'" fax="'.$customerAddress->getFax().'"
							vat_id="'.$customerAddress->getVatId().'" region_id="'.$customerAddress->getRegionId().'" street="'.$street.'">';
				if($customer->getAddressById($_pAddsses)->getData()){
					$address = $customer->getAddressById($_pAddsses)->format('html');
					$address = str_replace('<br />',',',$address);
					$address = str_replace('<br/>',',',$address);
					$address = str_replace('<br>',',',$address);
					$address = str_replace('<br >',',',$address);
					$html .= $address;
				}else{
					$html .= $customer->getFirstname().' '.$customer->getLastname();
				}
			}else{
				$html .= '<li id="'.$customer->getId().'" email="'.$customer->getEmail().'" prefix="'.$customer->getPrefix().'" firstname="'.$customer->getFirstname().'" middlename="'.$customer->getMiddlename().'"
							lastname="'.$customer->getLastname().'" suffix="'.$customer->getSuffix().'" telephone="'.$customer->getTelephone().'">';
				$html .= $customer->getFirstname().' '.$customer->getLastname();
			}
			$html .= '</li>';
		}
		if(!count($customers)){
			$html .= '<li>No customer</li>';
		}
		$html .= '</ul>';
		echo $html;
	}
	
	public function cartAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}
	
	public function reloadGiftwrapAction()
	{
		$helper = Mage::helper('onestepcheckout');
		$amount = $helper->getGiftwrapAmount();		
		$giftwrapAmount = 0;
		// $freeBoxes = 0;
		if($helper->getGiftwrapType() == 1){
			$items = Mage::getSingleton('checkout/cart')->getItems();
			foreach($items as $item){			
				if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }
				$giftwrapAmount += $amount * ($item->getQty());
			}
		}else 
			$giftwrapAmount = $amount;
			
		$result = Mage::helper('checkout')->formatPrice($giftwrapAmount);
		if($giftwrapAmount && $giftwrapAmount>0)
			$result .= '<input id="hidden_price" type="hidden" value="'.$giftwrapAmount.'">';
		$this->getResponse()->setBody($result);
	}
	
	public function orderlistSearchAction()
	{
		// $key = 'n2';
		// $collections = Mage::getModel('sales/order')->getCollection()
					// ->getSelect()->where('customer_firstname like "%'.$key.'%"
										// OR customer_lastname like "%'.$key.'%"
										// OR customer_middle like "%'.$key.'%"
										// ')->__toString();
			// Zend_Debug::dump($collections);die();
		// foreach($collections as $collection){
		// }
		// die();
		$this->loadLayout(false);
		$this->renderLayout();		
	}
			
	public function reload_discountAction()
	{
		$discount = $this->getRequest()->getParam('custom-discount');
		if(!$discount){
			$discount = 0;
		}
		$session = Mage::getSingleton('checkout/session');
        if(!$remove){
            $session->setData('onestepcheckout_admin_discount', $discount);
        }
        else{
            $session->unsetData('onestepcheckout_admin_discount');
        }        
        $this->loadLayout(false);
        $this->renderLayout();
	}	
	
	public function printInvoiceAction()
    {
        $cookie = Mage::getSingleton('core/cookie');
		$key = $cookie->get('onestepcheckout_admin_key');
		$code = $cookie->get('onestepcheckout_admin_code');
		$adminId = $cookie->get('onestepcheckout_admin_id');
		if(!$key || !$code || !$adminId)
			return $this;
			
        $this->loadLayout('print');
        $this->renderLayout();
    }
	
	public function savecashinAction()
	{
		$cashin = $this->getRequest()->getParam('cashin');
		Mage::getModel('core/session')->setData('onestepcheckout_cashin',$cashin);
	}
	
	public function checkcartAction()
	{
		$cart = Mage::getSingleton('checkout/cart');
		if(!$cart->getQuote()->getItemsCount()){
			echo 'noItem';
		}else{
			echo 'hasItem';
		}
	}
	
	public function save_customer_existAction()
	{
		$customerId = $this->getRequest()->getParam('customerId');
		if($customerId != '0'){
			Mage::getModel('checkout/session')->setData('onestepcheckout_customerid',$customerId);
		}else{
			Mage::getModel('checkout/session')->unsetData('onestepcheckout_customerid');
		}
	}
	
	public function viewOrderAction()
	{		
		$this->loadLayout(false);
		$this->renderLayout();
	}
}