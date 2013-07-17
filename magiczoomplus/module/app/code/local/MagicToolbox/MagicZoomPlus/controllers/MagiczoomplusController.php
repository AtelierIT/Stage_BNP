<?php

class MagicToolbox_MagicZoomPlus_MagiczoomplusController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {

        $this->_title($this->__('Magic Zoom Plus Settings'));
        $this->loadLayout()->_setActiveMenu('magictoolbox/magiczoomplus')->renderLayout();

    }

    public function addAction() {

        if($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('magiczoomplus/settings');
            list($package, $theme) = explode("/", $data['design']);
            $model->setPackage($package);
            $model->setTheme($theme);
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('magiczoomplus')->__('Settings was successfully added'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
            } catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
            }

        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('magiczoomplus')->__('Unable to add settings'));
        }
        $this->_redirect('*/*/');

    }

    public function deleteAction() {

        $id = $this->getRequest()->getParam('id');
        if($id > 0) {
            try {
                $model = Mage::getModel('magiczoomplus/settings')->load($id);
                if($model->getPackage() != 'all') {
                    $model->delete();
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Settings was successfully deleted'));
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('You can not delete default settings!'));
                }
            } catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');

    }

    public function massDeleteAction() {

        $ids = $this->getRequest()->getParam('massactionId');
        $alert = 0;
        if(!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select rows'));
        } else {
            try {
                foreach($ids as $id) {
                    $model = Mage::getModel('magiczoomplus/settings')->load($id);
                    if($model->getPackage() != 'all') {
                        $model->delete();
                    } else {
                        $alert = 1;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d row(s) were successfully deleted', count($ids)-$alert)
                );
                if($alert) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('You can not delete general settings!')
                    );
                }
            } catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');

    }

    public function editAction() {

        $id = $this->getRequest()->getParam('id');
        $model  = Mage::getModel('magiczoomplus/settings')->load($id);
        if($model->getId()) {
            Mage::register('magiczoomplus_model_data', $model);
            $this->_title($this->__('Magic Zoom Plus Settings'));
            $this->loadLayout();
            $this->_setActiveMenu('magictoolbox/magiczoomplus');
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('magiczoomplus')->__('Settings does not exist'));
            $this->_redirect('*/*/');
        }

    }

    public function saveAction() {

        if($params = $this->getRequest()->getPost()) {
            $id = $this->getRequest()->getParam('id');
            $model = Mage::getModel('magiczoomplus/settings');


            $data = array();
            $data['value'] = serialize($params['magiczoomplus']);
            $data['last_edit_time'] = now();
            $model->setData($data)->setId($id);
            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('magiczoomplus')->__('Settings was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array(
                        'id'        => $id,
                        '_current'  => true,
                        'back'      => null
                    ));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch(Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($params);
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('magiczoomplus')->__('Unable to find settings to save'));
        $this->_redirect('*/*/');

    }

    public function validateAction() {

        $response = new Varien_Object();
        $response->setError(false);
        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             */
        }
        catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
            $response->setError(true);
            $response->setAttribute($e->getAttributeCode());
            $response->setMessage($e->getMessage());
        }
        catch (Mage_Core_Exception $e) {
            $response->setError(true);
            $response->setMessage($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_initLayoutMessages('adminhtml/session');
            $response->setError(true);
            $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
        }
        $this->getResponse()->setBody($response->toJson());

    }

}
