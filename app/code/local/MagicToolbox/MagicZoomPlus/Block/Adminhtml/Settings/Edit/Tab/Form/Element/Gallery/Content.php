<?php

class MagicToolbox_MagicZoomPlus_Block_Adminhtml_Settings_Edit_Tab_Form_Element_Gallery_Content extends Mage_Adminhtml_Block_Widget {

    public function __construct() {
        parent::__construct();
        $this->setTemplate('magiczoomplus/gallery.phtml');
    }

    protected function _prepareLayout() {
        $this->setChild('uploader',
            $this->getLayout()->createBlock('adminhtml/media_uploader')
        );

        $this->getUploader()->getConfig()
            ->setUrl(Mage::getModel('adminhtml/url')->addSessionParam()->getUrl('*/magiczoomplus_gallery/upload'))
            ->setFileField('image')
            ->setFilters(array(
                'images' => array(
                    'label' => Mage::helper('adminhtml')->__('Images (.gif, .jpg, .png)'),
                    'files' => array('*.gif', '*.jpg','*.jpeg', '*.png')
                )
            ));

        return parent::_prepareLayout();
    }

    public function getUploader() {
        return $this->getChild('uploader');
    }

    public function getUploaderHtml() {
        return $this->getChildHtml('uploader');
    }

    public function getJsObjectName() {
        return $this->getHtmlId() . 'JsObject';
    }

    public function getImagesJson() {
        $model = Mage::registry('magiczoomplus_model_data');
        if($model) {
            $data = $model->getData();
            if($data['value']) {
                $_params = unserialize($data['value']);
                if(isset($_params['customslideshowblock']['gallery'])) {
                    $images = Mage::helper('core')->jsonDecode($_params['customslideshowblock']['gallery']);
                    foreach($images as &$image) {
                        $image['url'] = $this->getMagiczoomplusMediaUrl($image['file']);
                    }
                    return Mage::helper('core')->jsonEncode($images);
                }
            }
        }
        return '[]';
    }

    public function getMagiczoomplusMediaUrl($file) {
        $file = str_replace(DS, '/', $file);
        if(substr($file, 0, 1) == '/') {
            $file = substr($file, 1);
        }
        return Mage::getBaseUrl('media').'magictoolbox/magiczoomplus/'.$file;
    }

}

