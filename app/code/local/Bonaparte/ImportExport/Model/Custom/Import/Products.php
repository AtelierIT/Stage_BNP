<?php
ini_set('memory_limit', '3072M');
/**
 * Stores the business logic for the custom product import
 */
class Bonaparte_ImportExport_Model_Custom_Import_Products extends Bonaparte_ImportExport_Model_Custom_Import_Abstract
{
    /**
     * Path to the Size Translation file
     *
     * @var string
     */
    const CONFIGURATION_FILE_SIZE_TRANSLATION = '/dump_files/xml/SizeTranslation.csv';

    /**
     * Path to
     *
     * @var string
     */
    const CONFIGURATION_FILE_INVENTORY = '/dump_files/WBN240S';
    /**
     * Path to
     *
     * @var string
     */
    const CONFIGURATION_FILE_STRUCTURE = '/dump_files/structure.xml';
    /**
     * Path to the product configuration XML files
     *
     * @var string
     */
    const CONFIGURATION_FILE_PATH = '/chroot/home/stagebon/upload/xml/product';       // server configuration
//    const CONFIGURATION_FILE_PATH = '/var/www/bonaparte/magento/dump_files/xml/test7'; // local developer station
//    const CONFIGURATION_FILE_PATH = '/var/www/upload/xml/product';
    /**
     * Path to the product pictures source files
     *
     * @var string
     */
    const PICTURE_BASE_PATH = '/chroot/home/stagebon/upload/pictures/';
//    const PICTURE_BASE_PATH = '/var/www/bonaparte/magento/dump_files/pictures/';
//    const PICTURE_BASE_PATH = '/var/www/upload/pictures/';
    /**
     * Path to the missing pictures file
     *
     * @var string
     */
    const MISSING_PICTURES_BASE_PATH = '/dump_files/missing_pictures_part1.csv';

    /**
     * Path to temporary import files
     *
     * @var string
     */
    const RESOURCES_BASE_PATH = '/dump_files/tmp_import_resources_part1.csv';
    const STYLES_BASE_PATH = '/dump_files/tmp_import_styles_part1.csv';

    /**
     * Path to the missing pictures file
     *
     * @var string
     */
    const SIZE_CONFIGURATION_PATH = '/dump_files/sizeConfiguration.properties';

    /**
     * Contains all sizes that need to be translated to short ERP name
     *
     * @var array
     */
    private $_customSizes = array();

    /**
     * Contains all size translations according to the category
     *
     * @var array
     */
    private $_sizeTranslate = array();

    /**
     * Contains all product QTYs, the keys are product SKUs
     *
     * @var array
     */
    private $_productInventory = array();

    /**
     * Contains the link between products and category tree, the keys are product BNP styles
     *
     * @var array
     */
    private $_productStructure = array();

    /**
     * Contains all active BNP catalogues
     *
     * @var array
     */
    private $_activeCatalogues = array();

    /**
     * Contains all BNP attributes
     *
     * @var array
     */
    private $_bnpAttributes = array();

    /**
     * Contains the attribute set id
     *
     * @var integer
     */
    private $_attributeSetIdd = 0;

    /**
     * Contains the attribute id of the attribute user for configurable product creation
     *
     * @var array
     */
    private $_attributeIdd = 0;
    private $_mediaGalleryId = 0;
    private $_baseImageId = 0;
    private $_smallImageId = 0;
    private $_thumbnailId = 0;
    private $_descriptionId = 0;
    private $_shortDescriptionId = 0;
    private $_nameId = 0;
    private $_metaTitleId = 0;
    private $_metaDescriptionId = 0;
    private $_sizeTranslateId = 0;


    /**
     * Contains all Websites IDs
     *
     * @var array
     */
    private $_allWebsiteIDs = array();

    private $_newProductCounter = 0;
    private $_productEntityTypeId = 0;
    private $_missingPictureFilePath = '';
    private $_fileHandlerPictures;
    private $_fileHandlerResources;
    private $_fileHandlerStyles;


    /**
     * Maps the website code the its store view id
     *
     * @var array
     */
    private $_websiteStoreView = array();


    /**
     * Construct import model
     */
    public function _construct()
    {
        $this->_logMessage('Start PRODUCT IMPORT');
        $this->_configurationFilePath = array();
        $configFilesPath = self::CONFIGURATION_FILE_PATH;
        $files = scandir($configFilesPath);
        $this->_logMessage('There are ' . (count($files) - 2) . 'files');
        foreach ($files as $fileName) {
            if (strlen($fileName) < 3) {
                continue;
            }
            $this->_configurationFilePath[] = $configFilesPath . '/' . $fileName;
        }
        unset($fileName);

        if (!is_array($this->_configurationFilePath)) {
            return parent::_initialize();
        }

        $limit = 5000; //change the limit if the number of files is greater than 5000
        $counter = 0;
        foreach ($this->_configurationFilePath as $filePath) {
            if ($counter == $limit) {
                break;
            }
            $this->_data[] = new Varien_Simplexml_Config($filePath);
            $counter++;
        }

        $this->_productEntityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $this->_descriptionId = $this->_getAttributeID('description');
        $this->_shortDescriptionId = $this->_getAttributeID('short_description');
        $this->_nameId = $this->_getAttributeID('name');
        $this->_metaTitleId = $this->_getAttributeID('meta_title');
        $this->_metaDescriptionId = $this->_getAttributeID('meta_description');
        $this->_sizeTranslateId = $this->_getAttributeID('bnp_sizetranslate');

        foreach(Mage::app()->getWebsites() as $website) {
            $this->_websiteStoreView[strtolower($website->getCode())] = array_pop($website->getStoreIds());
        }

        $productsStructure = new Varien_Simplexml_Config(Mage::getBaseDir() . self::CONFIGURATION_FILE_STRUCTURE);
        $this->_getProductFolder($productsStructure);
//        $this->_missingPictureFilePath = Mage::getBaseDir() . self::MISSING_PICTURES_BASE_PATH;
    }

    /**
     * Contruct array with links between the products and category tree
     *
     */
    public function _getProductFolder($node){

        if ($node instanceof Varien_Simplexml_Config) {
            $folder = $node->getNode('Folder');
        } else {
            $folder = $node->Folder;
        }

        if (empty($folder)) {
            return;
        }

        foreach ($folder as $node) {
            $categoryId = $node->getAttribute('groupId');
            $Products = (array)$node->Products;
            foreach ($Products['Product'] as $BNPstyle){
                $this->_productStructure[$BNPstyle->getAttribute('idRef')][]= $categoryId;
            }
            $this->_getProductFolder($node);
        }
    }

    /**
     * Construct array with attributes options ids
     *
     * @param $attributeCode
     * @param $label
     *
     * @return integer
     */
    public function _getAttributeLabelId($attributeCode, $label)
    {
        if (isset($this->_bnpAttributes[$attributeCode])) {
            return $this->_bnpAttributes[$attributeCode][$label];
        }

        $productModel = Mage::getModel('catalog/product');
        $attributeBnpCatalogue = $productModel->getResource()->getAttribute($attributeCode);

        foreach ($attributeBnpCatalogue->getSource()->getAllOptions() as $option) {
            $this->_bnpAttributes[$attributeCode][$option['label']] = $option['value'];
        }

        return $this->_bnpAttributes[$attributeCode][$label];

    }

    /**
     * Get the attributes set id
     *
     * @param $label - attributes label
     */
    public function _getAttributeSetID($label)
    {
        $SetId  = intval(Mage::getModel('eav/entity_attribute_set')->getCollection()->setEntityTypeFilter($this->_productEntityTypeId)->addFieldToFilter('attribute_set_name', $label)->getFirstItem()->getAttributeSetId());
        $this->_attributeSetIdd = $SetId ;
    }

    /**
     * Get the attribute id
     *
     * @param $label - attributes label
     * @return integer
     */
    public function _getAttributeID($label)
    {
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $attr_id = $eavAttribute->getIdByCode('catalog_product', $label);
        return $attr_id;
    }

    /**
     * Get the custom sizes from the Translation file
     */
    public function _getCustomSize()
    {
        $customSize = array();
        $handle = fopen(Mage::getBaseDir() . self::CONFIGURATION_FILE_SIZE_TRANSLATION, 'r');
        while ($data_csv = fgetcsv($handle, null, ';', '"')) {
            $customSize[$data_csv[3]] = $data_csv[2];
        }
        fclose($handle);
        return $customSize;
    }

    /**
     * Get the product inventory from file
     *
     * @return array
     */
    public function _getProductInventory()
    {
        $productInventory = array();
        $handle = fopen(Mage::getBaseDir() . self::CONFIGURATION_FILE_INVENTORY, 'r');
        while ($data_csv = fgets($handle)) {
            $data_csv = explode(';', $data_csv);
            $headArticleExploded = explode('-', $data_csv[1]);
            $productInventory[$headArticleExploded[1] . '-' . $data_csv[6]] = $data_csv[8];
        }
        fclose($handle);

        return $productInventory;
    }

    /**
     * Get the product inventory from file
     *
     * @return array
     */
    public function _getSizeConfiguration()
    {
        $sizeConfig = array();
        $handle = fopen(Mage::getBaseDir() . self::SIZE_CONFIGURATION_PATH, 'r');
        while ($data_csv = fgets($handle)) {
            if (($data_csv[0]=='#') or (trim($data_csv)=='')) continue;
            $data_csv = explode('=', trim($data_csv));
            $sizesPairsExploded = explode('/', $data_csv[1]);
            foreach ($sizesPairsExploded as $sizePair){
                $explodedSizes = explode (',', $sizePair);
                $sizeConfig[str_replace('-','_',$data_csv[0])][$explodedSizes[0]]=$explodedSizes[1];
            }
        }
        fclose($handle);

        return $sizeConfig;
    }

    /**
     * Add attribute option
     */
    public function addAttributeOption($arg_attribute, $arg_value)
    {
        $attribute_model = Mage::getModel('eav/entity_attribute');
        $attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');

        $attribute_code = $attribute_model->getIdByCode('catalog_product', $arg_attribute);
        $attribute = $attribute_model->load($attribute_code);

        $attribute_table = $attribute_options_model->setAttribute($attribute);
        $options = $attribute_options_model->getAllOptions(false);

        $value['option'] = array($arg_value, $arg_value);
        $result = array('value' => $value);
        $attribute->setData('option', $result);
        $attribute->save();

        // add AttributeOptionExternalInternalIdRelation
        $internalId = $this->_getAttributeLabelId($arg_attribute, $arg_value);
//        Mage::getModel('Bonaparte_ImportExport/External_Relation_Attribute_Option')
//            ->setType(Bonaparte_ImportExport_Model_External_Relation_Attribute_Option::TYPE_ATTRIBUTE_OPTION)
//            ->setExternalId($arg_value)
//            ->setInternalId($internalId)
//            ->setAttributeCode($arg_attribute)
//            ->save()
//            ->clearInstance();

        return $internalId;
    }


    /**
     * Function copy all images from feeding folder to magento /media folder
     *
     */
    private function _getProductImageReady()
    {


        $images = scandir(self::PICTURE_BASE_PATH);
        $_mediaBase = Mage::getBaseDir('media') . '/catalog/product/';

        $pictureNumber = count($images);
        $this->_logMessage($pictureNumber . ' in ' . $_mediaBase);
        $counter = 1;
        foreach ($images as $image) {
            if (in_array($image, array('.', '..')))
                continue;

            $firstDir = $_mediaBase . $image[0];
            $secondDir = $firstDir . '/' . $image[1];
            $path = $secondDir . '/' . $image;

            if (!file_exists($path)) {
                if (!file_exists($secondDir)) mkdir($secondDir, 0775, true);

                $this->_logMessage('Creating ' . $counter++ . ' of ' . $pictureNumber . ' - from ' . self::PICTURE_BASE_PATH . $image . ' - to ' . $path);
                copy(self::PICTURE_BASE_PATH . $image, $path); // Stage version
                //copy(Mage::getBaseDir().self::PICTURE_BASE_PATH.$image, $path); //localhost version

            } else $this->_logMessage('Existing ' . $counter++ . ' of ' . $pictureNumber . ' - ' . $image);
        }


    }

    /**
     * Function build to replace the MAGENTO addImageToMediaGallery
     *
     */
    private function _addProductImage($productID, $pictureName, $isLeadPicture)
    {
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');
        $connW = Mage::getSingleton('core/resource')->getConnection('core_write');
        $pictureValueField = '/' . $pictureName[0] . '/' . $pictureName[1] . '/' . $pictureName;

        /*
           *    Check the existing images
           */

        $sql = "SELECT * FROM catalog_product_entity_media_gallery WHERE entity_id IN (" . $productID . ") AND value IN ('" . $pictureValueField . "');";
        $_galleryImgs = $conn->fetchAll($sql);

        if (!$_galleryImgs) {
            $sql = "INSERT INTO catalog_product_entity_media_gallery (attribute_id, entity_id, value) VALUES (" . $this->_mediaGalleryId . "," . $productID . ",'" . $pictureValueField . "');";
            $connW->query($sql);
        }


        if ($isLeadPicture){

            $sql = "DELETE FROM catalog_product_entity_varchar WHERE entity_id IN (" . $productID . ") AND attribute_id IN (" . $this->_baseImageId . "," . $this->_smallImageId . "," . $this->_thumbnailId . ");
                    INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (" . $this->_productEntityTypeId . "," . $this->_baseImageId . ",0," . $productID . ",'" . $pictureValueField . "'), (" . $this->_productEntityTypeId . "," . $this->_smallImageId . ",0," . $productID . ",'" . $pictureValueField . "'), (" . $this->_productEntityTypeId . "," . $this->_thumbnailId . ",0," . $productID . ",'" . $pictureValueField . "');";
            $connW->query($sql);

        }else{

            $sql = "SELECT * FROM catalog_product_entity_varchar WHERE entity_id IN (" . $productID . ") AND attribute_id IN (" . $this->_baseImageId . "," . $this->_smallImageId . "," . $this->_thumbnailId . ");";
            $_imageAssoc = $conn->fetchAll($sql);

            if (!$_imageAssoc) {
                $sql = "INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (" . $this->_productEntityTypeId . "," . $this->_baseImageId . ",0," . $productID . ",'" . $pictureValueField . "'), (" . $this->_productEntityTypeId . "," . $this->_smallImageId . ",0," . $productID . ",'" . $pictureValueField . "'), (" . $this->_productEntityTypeId . "," . $this->_thumbnailId . ",0," . $productID . ",'" . $pictureValueField . "');";
                $connW->query($sql);
            }
        }

    }

    /**
     * Function to return an array of active Catalogues
     *
     */
    private function _getActiveCatalogues()
    {
        $conn = Mage::getSingleton('core/resource')->getConnection('core_read');

        $sql = "SELECT name FROM bonaparte_importexport_catalogue WHERE end_date is NULL;";
        $_activeCatalogues = $conn->fetchAll($sql);
        $_list = array();
        foreach ($_activeCatalogues as $catalog){
            $_list[] = $catalog[name];
        }
        return $_list;
    }


    /**
     * Function to delete all products
     */
    private function _deleteAllProducts()
    {
        $products = Mage::getResourceModel('catalog/product_collection')->getAllIds();
        foreach ($products as $key => $productId) {
            try {
                $product = Mage::getSingleton('catalog/product')->load($productId);
                Mage::dispatchEvent('catalog_controller_product_delete', array('product' => $product));
                $this->_logMessage('Deleting product id' . $productId);
                $product->delete();
            } catch (Exception $e) {
                $this->_logMessage('Could not delete product id' . $productId);
            }
        }
    }

    /**
     * Add new option to the attribute options and return the id
     *
     * @param array $attributeData
     *
     * @return integer
     */
    private function _addAttributeOptionsForBnpStylenbr($attributeData) {
        $model = Mage::getModel('catalog/resource_eav_attribute')->load('bnp_stylenbr', 'attribute_code');

        $options = $model->getSource()->getAllOptions(false);
        $optionLabels = array();
        foreach($options as $option) {
            $optionLabels[$option['label']] = true;
        }
        unset($option);

        if(!isset($optionLabels[$attributeData['option']['value']['option0'][0]])) {
            $model->addData($attributeData);
            $model->save();
        }

        $options = $model->getSource()->getAllOptions(false);
        foreach($options as $option) {
            if($option['label'] == $attributeData['option']['value']['option0'][0]) {
                $attributeOptionId = $option['value'];
                break;
            }
        }

        return $attributeOptionId;
    }

    /**
     * Function build to add product to database
     *
     * @param array $productData
     */
    private function _addProduct($productData)
    {
        $connW = Mage::getSingleton('core/resource')->getConnection('core_write');
        $configurable_attribute = "bnp_size";
        $attr_id = $this->_attributeIdd;
        $cino_picture_directory = Mage::getBaseDir('media') . '/cino/';

        $pictureBasePath = self::PICTURE_BASE_PATH;

        //$mediaAttributes = array('image','thumbnail','small_image');

        $this->_logMessage('Editing ' . count($productData['Items']['value']) . ' from this file');
        $productCounter = 1;
        $productItemIDs = array();
        foreach ($productData['Items']['value'] as $productItem) {

            $simpleProducts = array();
            $this->_logMessage($productCounter++ . ' Configuring product details ...');

            $productSizes = array();


            //all products have EU sizes and frontend custom size
            if (in_array($productItem['Sizess']['value']['da'], array('50X100', '70X140', 'ne size', 'one size', 'One size', 'One Size', 'ONE SIZE', 'onesize', 'Onesize', 'ONESIZE'))) {
                    $productSizes = array('cst_' . $productItem['Sizess']['value']['da']);
                } elseif (!$this->_customSizes[$productItem['Sizess']['value']['da']]) {
                    $productItemSizess = $productItem['Sizess']['value']['da'];
                    $productSizesTemp = explode("-", $productItemSizess);
                    foreach ($productSizesTemp as $productSizeTemp)
                        if (!$this->_customSizes[$productSizeTemp]) {
                            $productSizes[] = $productSizeTemp;
                        } else {
                            $productSizes[] = $this->_customSizes[$productSizeTemp];
                        }

                } else {
                    $productSizes = array($this->_customSizes[$productItem['Sizess']['value']['da']]);
            }

            $this->_logMessage('Editing ' . count($productSizes) . ' simple products');
            $productOneSize = (count($productSizes) == 1)? 1 : 0;

            $sizeCounter = 0;
            $simpleProductData = array();

            foreach ($productSizes as $productSize) {
                $this->_logMessage('.', false);
                $sizeCounter++;
                $attr_value = $productSize;
                $configurableAttributeOptionId = $this->_getAttributeLabelId($configurable_attribute, $productSize);
                if (!$configurableAttributeOptionId) {
                    $configurableAttributeOptionId = $this->addAttributeOption($configurable_attribute, $attr_value);
                }

                //create each simple product
                $category_ids = array();
                $category_idss = array();

                $category_ids = $this->_productStructure[$productData['StyleNbr']['value']];
                foreach ($category_ids as $category_id) {
                    $category = Mage::getModel('catalog/category')->getCollection()->addAttributeToFilter('old_id', $category_id)->load();
                    foreach ($category->getAllIds() as $idss) $category_idss [] = $idss;

                }

                $productShortDescription = explode(".", $productData['DescriptionCatalogues']['value']['en']);

                // BEGIN external id relate to internal id
                $externalIds = array_merge(
                    array(
                        $productItem['Color']['value'],
                        $productData['Fitting']['value'],
                        $productData['Composition']['value'],
                        $productData['Concept']['value'],
                        $productData['Program']['value'],
                        $productData['ProductMainGroup']['value'],
                        $productData['ProductGroup']['value'],
                        $productData['ProductSubGroup']['value']
                    ),
                    (array)$productData['Catalogue']['value'],
                    (array)$productData['Season']['value'],
                    (array)$productItem['WashIcon']['value']
                );

                foreach ($externalIds as $key => $value) {
                    if (empty($value)) {
                        unset($externalIds[$key]);
                    } else {
                        $externalIds[$key] = (string)$externalIds[$key];
                    }
                }
                $collection = Mage::getModel('Bonaparte_ImportExport/External_Relation_Attribute_Option')
                    ->getCollection()
                    ->addFieldToFilter('external_id', array('in' => $externalIds))
                    ->addFieldToFilter('attribute_code', array('in' => array(
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_CATALOGUE,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_COLOR,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_COMPOSITION,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_CONCEPT,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_FITTING,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_GROUP,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_MAIN_GROUP,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PROGRAM,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_SEASON,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_SUB_GROUP,
                        Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_WASH_ICON
                    )))
                    ->load();

                $externalIdToInternalId = array();
                foreach ($collection as $relation) {
                    $externalIdToInternalId[$relation->getExternalId() . '_' . $relation->getAttributeCode()] = $relation->getInternalId();
                }
                // END external id relate to internal id

                $bnpCatalogueLabelIds = array();
                foreach ($productData['Catalogue']['value'] as $externalId) {
                    $bnpCatalogueLabelIds[] = $externalIdToInternalId[$externalId . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_CATALOGUE];
                }
                $bnpSeasonLabelIds = array();
                foreach ($productData['Season']['value'] as $externalId) {
                    $bnpSeasonLabelIds[] = $externalIdToInternalId[$externalId . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_SEASON];
                }
                $bnpWashiconLabelIds = array();
                foreach ($productItem['WashIcon']['value'] as $externalId) {
                    $bnpWashiconLabelIds[] = $externalIdToInternalId[$externalId . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_WASH_ICON];
                }

                $productSKU = ($productOneSize && !$this->_customSizes[$productItem['Sizess']['value']['da']]) ? $productItem['CinoNumber']['value'] : $productItem['CinoNumber']['value'] . '-' . $productSize;

               //check if the product exists in magento then get product and update else create product
                //  $sProduct = Mage::getModel('catalog/product')->loadByAttribute('sku',$productSKU);

                $sProduct = Mage::getModel('catalog/product');
                $productId = Mage::getModel('catalog/product')->getIdBySku($productSKU);
                if ($productId) {
                    $sProduct->load($productId);
                }else{
                    $sProduct = Mage::getModel('catalog/product');
                    $sProduct
                        ->setSku($productSKU)
                        ->setAttributeSetId($this->_attributeSetIdd)
						->setPrice("1000.00")
						->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
						->setTaxClassId(0) //none
						->setWeight(1)
                        ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                        ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                    $this->_newProductCounter++;
                    $productQTY = (!is_null($this->_productInventory[$productSKU])) ? $this->_productInventory[$productSKU] : "0";

                    $sProduct->setStockData(array(
                        'is_in_stock' => (($productQTY > 0) ? 1 : 0),
                        'qty' => $productQTY
                    ));
                };

                foreach($this->_allWebsiteIDs as $code => $websiteId) {
                    if(!$websiteId) {
                        unset($this->_allWebsiteIDs[$code]);
                    }
                }

                $sProduct -> setWebsiteIds($this->_allWebsiteIDs);

                //UK size translation
                //$sProduct -> setBnpSizetranslate($productSize);
                $uksize = $productSize;
                foreach ($category_ids as $category_id){
                    if ($this->_sizeTranslate[$category_id][$productSize]){
                        //$sProduct -> setBnpSizetranslate($this->_sizeTranslate[$category_id][$productSize]);
                        $uksize = $this->_sizeTranslate[$category_id][$productSize];
                        $simpleProductData[$sizeCounter]['uk_sku'] = $productItem['CinoNumber']['value'] . '-' . $this->_sizeTranslate[$category_id][$productSize];
                    }
                }
                $ukSizeCategory = $productData['Program']['value'] . '_' . $productData['ProductMainGroup']['value'];
                if ($this->_sizeTranslate[$ukSizeCategory][$productSize]){
                    //$sProduct -> setBnpSizetranslate($this->_sizeTranslate[$ukSizeCategory][$productSize]);
                    $uksize = $this->_sizeTranslate[$ukSizeCategory][$productSize];
                    $simpleProductData[$sizeCounter]['uk_sku'] = $productItem['CinoNumber']['value'] . '-' . $this->_sizeTranslate[$ukSizeCategory][$productSize];
                }

                // add stylenbr to select options
                $bnpStylenbrAttributeOptionId = $this->_addAttributeOptionsForBnpStylenbr(array(
                    'option' => array(
                        'value' => array(
                            'option0' => array(
                                0 => $productData['StyleNbr']['value']
                            )
                        )
                    )
                ));

                $sProduct
                    ->setName($productData['HeaderWebs']['value']['en'])
                    ->setDescription($productData['DescriptionCatalogues']['value']['en'])
                    ->setShortDescription($productShortDescription[0] . '.')

                    ->setMetaTitle($productData['HeaderWebs']['value']['en'])
                    ->setMetaKeywords('')
                    ->setMetaDescription($productData['DescriptionCatalogues']['value']['en'])

                    ->setCategoryIds($category_idss)

                    ->setBnpStylenbr($bnpStylenbrAttributeOptionId)
                    ->setBnpColor($externalIdToInternalId[$productItem['Color']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_COLOR])
                    ->setBnpFitting($externalIdToInternalId[$productData['Fitting']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_FITTING])
                    ->setBnpCatalogue(implode(',', $bnpCatalogueLabelIds))
                    ->setBnpSeason(implode(',', $bnpSeasonLabelIds))
                    ->setBnpWashicon(implode(',', $bnpWashiconLabelIds))
                    ->setBnpColorgroup($this->_getAttributeLabelId("bnp_colorgroup", $productItem['ColorGroup']['value']))
                    ->setBnpMeasurechartabrv($productData['MeasureChartAbrv']['value'])
                    ->setBnpMeasurementchart($productItem['MeasurementChart']['value'])
                    ->setBnpProgram($externalIdToInternalId[$productData['Program']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PROGRAM])
                    ->setBnpProductmaingroup($externalIdToInternalId[$productData['ProductMainGroup']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_MAIN_GROUP])
                    ->setBnpProductgroup($externalIdToInternalId[$productData['ProductGroup']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_GROUP])
                    ->setBnpProductsubgroup($externalIdToInternalId[$productData['ProductSubGroup']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_SUB_GROUP])
                    ->setBnpComposition($externalIdToInternalId[$productData['Composition']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_COMPOSITION])
                    ->setBnpConcept($externalIdToInternalId[$productData['Concept']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_CONCEPT])


                    ->setUrlKey($productData['HeaderWebs']['value']['en'] . '_' . $productItem['CinoNumber']['value'] . '_' . $productSize)
                    ->setData($configurable_attribute, $configurableAttributeOptionId);



                try {
                    $sProduct->save();
                    // saving some data for configurable product creation
                    $sProductId = $sProduct->getId();
                    array_push(
                        $simpleProducts,
                        array(
                            "id" => $sProductId,
                            "price" => $sProduct->getPrice(),
                            "attr_code" => $configurable_attribute,
                            "attr_id" => $attr_id,
                            "value" => $configurableAttributeOptionId,
                            "label" => $attr_value
                        )
                    );
                    $simpleProductData[$sizeCounter]['entity_id'] = $sProductId;
                    $simpleProductData[$sizeCounter]['sku'] = $productSKU;


                    // adding the item images
                    $itemLeadPicture = 0;
                    foreach ($productItem['Resources']['value'] as $resource) {
                        $isLeadPicture = 0;
                        if (count($productItem['LeadPicture']['value'])==2 && $resource['ImageType']['value']=="packshots")
                        {
                            if ($productItem['LeadPicture']['value'][0]['id']==$resource['id'])
                                {
                                $isLeadPicture = 1; $itemLeadPicture=1;
                            } elseif ($productItem['LeadPicture']['value'][1]['id']==$resource['id']) {
                                $isLeadPicture = 1; $itemLeadPicture=1;
                            }
                        }elseif (count($productItem['LeadPicture']['value'])==1 && $productItem['LeadPicture']['value'][0]['id']==$resource['id']){ $isLeadPicture = 1;$itemLeadPicture=1;};
                        $picturePath = $pictureBasePath . $resource['OriginalFilename']['value'];
                        if (file_exists($picturePath) && ($resource['OriginalFilename']['value'] != '')) {
                            try {
                                $this->_addProductImage($sProductId, $resource['OriginalFilename']['value'], $isLeadPicture);
                                $this->_logMessage('O', false);
                                fputcsv($this->_fileHandlerResources,array($sProductId, $resource['ResourceFileId']['value'], $resource['OriginalFilename']['value'], 0,$resource['ImageType']['value'], $isLeadPicture));

                            } catch (Exception $e) {
                                echo $e->getMessage();
                            }
                        } else {
                            $this->_logMessage('X', false);
                        }
                    }
                    if (!$itemLeadPicture){
                        foreach ($productData['Resources']['value'] as $resource) {


                            $isLeadPicture = 0;
                            if (count($productItem['LeadPicture']['value'])==2 && $resource['ImageType']['value']=="packshots")
                            {
                                if ($productItem['LeadPicture']['value'][0]['id']==$resource['id'])
                                {
                                    $isLeadPicture = 1;
                                } elseif ($productItem['LeadPicture']['value'][1]['id']==$resource['id']) {
                                    $isLeadPicture = 1;
                                }
                            }elseif (count($productItem['LeadPicture']['value'])==1 && $productItem['LeadPicture']['value'][0]['id']==$resource['id']){ $isLeadPicture = 1;};
                            $picturePath = $pictureBasePath . $resource['OriginalFilename']['value'];
                            if ($isLeadPicture && file_exists($picturePath) && ($resource['OriginalFilename']['value'] != '')) {
                                try {
                                    $this->_addProductImage($sProductId, $resource['OriginalFilename']['value'], $isLeadPicture);
                                    $this->_logMessage('O', false);
                                    fputcsv($this->_fileHandlerResources,array($sProductId, $resource['ResourceFileId']['value'], $resource['OriginalFilename']['value'], 0,$resource['ImageType']['value'], $isLeadPicture));

                                } catch (Exception $e) {
                                    echo $e->getMessage();
                                }
                            } else {
                                $this->_logMessage('X', false);
                            }



                        }
                    }

                    // adding the different attribute values per store view
                    $productShortDescriptionn = array();
                    foreach ($productData['DescriptionCatalogues']['value'] as $key => $description){
                        $temp = explode('.',$description);
                        $productShortDescriptionn [$key] = $temp[0].'.';
                    }

                    $sql = "INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:short_descr_id,:store_id,:entity_id,:short_description)ON DUPLICATE KEY UPDATE `value` = :short_description;
                            INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:descr_id,:store_id,:entity_id,:description)ON DUPLICATE KEY UPDATE `value` = :description;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:meta_descr_id,:store_id,:entity_id,:meta_description)ON DUPLICATE KEY UPDATE `value` = :meta_description;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:meta_title_id,:store_id,:entity_id,:meta_title)ON DUPLICATE KEY UPDATE `value` = :meta_title;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:name_id,:store_id,:entity_id,:name)ON DUPLICATE KEY UPDATE `value` = :name;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:size_translate_id,:store_id,:entity_id,:size_translate)ON DUPLICATE KEY UPDATE `value` = :size_translate;

                            ";

                    if($this->_websiteStoreView['uk']) {
                        $binds = array(
                            'entity_type_id'    => $this->_productEntityTypeId,
                            'short_descr_id'    => $this->_shortDescriptionId,
                            'descr_id'          => $this->_descriptionId,
                            'meta_descr_id'     => $this->_metaDescriptionId,
                            'meta_title_id'     => $this->_metaTitleId,
                            'name_id'           => $this->_nameId,
                            'size_translate_id' => $this->_sizeTranslateId,
                            'store_id'          => $this->_websiteStoreView['uk'],
                            'entity_id'         => $sProductId,
                            'short_description' => $productShortDescriptionn['en'],
                            'description'       => $productData['DescriptionCatalogues']['value']['en'],
                            'meta_description'  => $productShortDescriptionn['en'],
                            'meta_title'        => $productData['HeaderWebs']['value']['en'],
                            'name'              => $productData['HeaderWebs']['value']['en'],
                            'size_translate'    => $uksize

                        );
                        $connW->query($sql, $binds);
                    }

                    if($this->_websiteStoreView['dk']) {
                        $binds = array(
                            'entity_type_id'    => $this->_productEntityTypeId,
                            'short_descr_id'    => $this->_shortDescriptionId,
                            'descr_id'          => $this->_descriptionId,
                            'meta_descr_id'     => $this->_metaDescriptionId,
                            'meta_title_id'     => $this->_metaTitleId,
                            'name_id'           => $this->_nameId,
                            'size_translate_id' => $this->_sizeTranslateId,
                            'store_id'          => $this->_websiteStoreView['dk'],
                            'entity_id'         => $sProductId,
                            'short_description' => $productShortDescriptionn['da'],
                            'description'       => $productData['DescriptionCatalogues']['value']['da'],
                            'meta_description'  => $productShortDescriptionn['da'],
                            'meta_title'        => $productData['HeaderWebs']['value']['da'],
                            'name'              => $productData['HeaderWebs']['value']['da'],
                            'size_translate'    => $productSize
                        );
                        $connW->query($sql, $binds);
                    }

                    if($this->_websiteStoreView['ch']) {
                        $binds = array(
                            'entity_type_id'    => $this->_productEntityTypeId,
                            'short_descr_id'    => $this->_shortDescriptionId,
                            'descr_id'          => $this->_descriptionId,
                            'meta_descr_id'     => $this->_metaDescriptionId,
                            'meta_title_id'     => $this->_metaTitleId,
                            'name_id'           => $this->_nameId,
                            'size_translate_id' => $this->_sizeTranslateId,
                            'store_id'          => $this->_websiteStoreView['ch'],
                            'entity_id'         => $sProductId,
                            'short_description' => $productShortDescriptionn['de_CH'],
                            'description'       => $productData['DescriptionCatalogues']['value']['de_CH'],
                            'meta_description'  => $productShortDescriptionn['de_CH'],
                            'meta_title'        => $productData['HeaderWebs']['value']['de_CH'],
                            'name'              => $productData['HeaderWebs']['value']['de_CH'],
                            'size_translate'    => $productSize
                        );
                        $connW->query($sql, $binds);
                    }

                    if($this->_websiteStoreView['de']) {
                        $binds = array(
                            'entity_type_id'    => $this->_productEntityTypeId,
                            'short_descr_id'    => $this->_shortDescriptionId,
                            'descr_id'          => $this->_descriptionId,
                            'meta_descr_id'     => $this->_metaDescriptionId,
                            'meta_title_id'     => $this->_metaTitleId,
                            'name_id'           => $this->_nameId,
                            'size_translate_id' => $this->_sizeTranslateId,
                            'store_id'          => $this->_websiteStoreView['de'],
                            'entity_id'         => $sProductId,
                            'short_description' => $productShortDescriptionn['de'],
                            'description'       => $productData['DescriptionCatalogues']['value']['de'],
                            'meta_description'  => $productShortDescriptionn['de'],
                            'meta_title'        => $productData['HeaderWebs']['value']['de'],
                            'name'              => $productData['HeaderWebs']['value']['de'],
                            'size_translate'    => $productSize
                        );
                        $connW->query($sql, $binds);
                    }

                    if($this->_websiteStoreView['nl']) {
                        $binds = array(
                            'entity_type_id'    => $this->_productEntityTypeId,
                            'short_descr_id'    => $this->_shortDescriptionId,
                            'descr_id'          => $this->_descriptionId,
                            'meta_descr_id'     => $this->_metaDescriptionId,
                            'meta_title_id'     => $this->_metaTitleId,
                            'name_id'           => $this->_nameId,
                            'size_translate_id' => $this->_sizeTranslateId,
                            'store_id'          => $this->_websiteStoreView['nl'],
                            'entity_id'         => $sProductId,
                            'short_description' => $productShortDescriptionn['nl'],
                            'description'       => $productData['DescriptionCatalogues']['value']['nl'],
                            'meta_description'  => $productShortDescriptionn['nl'],
                            'meta_title'        => $productData['HeaderWebs']['value']['nl'],
                            'name'              => $productData['HeaderWebs']['value']['nl'],
                            'size_translate'    => $productSize
                        );
                        $connW->query($sql, $binds);
                    }

                    if($this->_websiteStoreView['se']) {
                        $binds = array(
                            'entity_type_id'    => $this->_productEntityTypeId,
                            'short_descr_id'    => $this->_shortDescriptionId,
                            'descr_id'          => $this->_descriptionId,
                            'meta_descr_id'     => $this->_metaDescriptionId,
                            'meta_title_id'     => $this->_metaTitleId,
                            'name_id'           => $this->_nameId,
                            'size_translate_id' => $this->_sizeTranslateId,
                            'store_id'          => $this->_websiteStoreView['se'],
                            'entity_id'         => $sProductId,
                            'short_description' => $productShortDescriptionn['sv'],
                            'description'       => $productData['DescriptionCatalogues']['value']['sv'],
                            'meta_description'  => $productShortDescriptionn['sv'],
                            'meta_title'        => $productData['HeaderWebs']['value']['sv'],
                            'name'              => $productData['HeaderWebs']['value']['sv'],
                            'size_translate'    => $productSize
                        );
                        $connW->query($sql, $binds);
                    }

                    $sProduct->clearInstance();

                } catch (Exception $e) {
                    echo "item " . $productData['HeaderWebs']['value']['en'] . " not added\n";
                    echo "exception:$e";
                }


            }

            // create the configurable product


            //check if the product exists in magento then get product and update else create product

            $cProduct = Mage::getModel('catalog/product');
            $productId = Mage::getModel('catalog/product')->getIdBySku($productItem['CinoNumber']['value'] . 'c');
            if ($productId) {
                $cProduct->load($productId);
            }else{
                $cProduct
                    ->setSku($productItem['CinoNumber']['value'] . 'c')
                    ->setAttributeSetId($this->_attributeSetIdd)
                    ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE)
					->setTaxClassId(0)
					->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
					->setWebsiteIds($this->_allWebsiteIDs)
                	->setPrice("1000.00");
                $this->_newProductCounter++;
                $cProduct->setCanSaveConfigurableAttributes(true);
                $cProduct->setCanSaveCustomOptions(true);

                $cProductTypeInstance = $cProduct->getTypeInstance();

                $cProductTypeInstance->setUsedProductAttributeIds(array($attr_id));
                $attributes_array = $cProductTypeInstance->getConfigurableAttributesAsArray();

                foreach ($attributes_array as $key => $attribute_array) {
                    $attributes_array[$key]['use_default'] = 1;
                    $attributes_array[$key]['position'] = 0;

                    if (isset($attribute_array['frontend_label'])) {
                        $attributes_array[$key]['label'] = $attribute_array['frontend_label'];
                    } else {
                        $attributes_array[$key]['label'] = $attribute_array['attribute_code'];
                    }
                }
                $cProduct->setConfigurableAttributesData($attributes_array);

                $dataArray = array();
                foreach ($simpleProducts as $simpleArray) {
                    $dataArray[$simpleArray['id']] = array();
                    foreach ($attributes_array as $attrArray) {
                        array_push(
                            $dataArray[$simpleArray['id']],
                            array(
                                "attribute_id" => $simpleArray['attr_id'],
                                "label" => $simpleArray['label'],
                                "is_percent" => false,
                                "pricing_value" => $simpleArray['price']
                            )
                        );
                    }
                }

                $cProduct->setConfigurableProductsData($dataArray);

                $cProduct->setStockData(array(
                    'use_config_manage_stock' => 1,
                    'is_in_stock' => 1,
                    'is_salable' => 1
                ));
            };

            // add stylenbr to select options
            $bnpStylenbrAttributeOptionId = $this->_addAttributeOptionsForBnpStylenbr(array(
                'option' => array(
                    'value' => array(
                        'option0' => array(
                            0 => $productData['StyleNbr']['value']
                        )
                    )
                )
            ));

            $cProduct

                ->setName($productData['HeaderWebs']['value']['en'])
                ->setDescription($productData['DescriptionCatalogues']['value']['en'])
                ->setShortDescription($productShortDescription[0] . '.')

                ->setMetaTitle($productData['HeaderWebs']['value']['en'])
                ->setMetaKeywords('')
                ->setMetaDescription($productData['DescriptionCatalogues']['value']['en'])

                ->setCategoryIds($category_idss)

                ->setBnpStylenbr($bnpStylenbrAttributeOptionId)
                ->setBnpColor($externalIdToInternalId[$productItem['Color']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_COLOR])
                ->setBnpFitting($externalIdToInternalId[$productData['Fitting']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_FITTING])
                ->setBnpCatalogue(implode(',', $bnpCatalogueLabelIds))
                ->setBnpSeason(implode(',', $bnpSeasonLabelIds))
                ->setBnpWashicon(implode(',', $bnpWashiconLabelIds))
                ->setBnpColorgroup($this->_getAttributeLabelId("bnp_colorgroup", $productItem['ColorGroup']['value']))
                ->setBnpMeasurechartabrv($productData['MeasureChartAbrv']['value'])
                ->setBnpMeasurementchart($productItem['MeasurementChart']['value'])
                ->setBnpProgram($externalIdToInternalId[$productData['Program']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PROGRAM])
                ->setBnpProductmaingroup($externalIdToInternalId[$productData['ProductMainGroup']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_MAIN_GROUP])
                ->setBnpProductgroup($externalIdToInternalId[$productData['ProductGroup']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_GROUP])
                ->setBnpProductsubgroup($externalIdToInternalId[$productData['ProductSubGroup']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_PRODUCT_SUB_GROUP])
                ->setBnpComposition($externalIdToInternalId[$productData['Composition']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_COMPOSITION])
                ->setBnpConcept($externalIdToInternalId[$productData['Concept']['value'] . '_' . Bonaparte_ImportExport_Model_Custom_Import_Attributes::CUSTOM_ATTRIBUTE_CODE_CONCEPT])

                ->setUrlKey($productData['HeaderWebs']['value']['en'] . '_' . $productItem['CinoNumber']['value']);



            $this->_logMessage('Saving configurable product');
            try {
                $cProduct->save();
                $cProductId = $cProduct->getId();
                $productItemIDs[]=$cProductId;

                foreach ($simpleProductData as $simpleProduct){
                    fputcsv($this->_fileHandlerStyles, array($productData['StyleNbr']['value'],$cProductId,$simpleProduct['entity_id'],$simpleProduct['sku'],$simpleProduct['uk_sku'] ));
                }

                // adding the images
                $resourceList = array();

                $itemLeadPicture = 0;
                foreach ($productItem['Resources']['value'] as $resource) {
                    $isLeadPicture = 0;
                    if (count($productItem['LeadPicture']['value'])==2 && $resource['ImageType']['value']=="packshots")
                    {
                        if ($productItem['LeadPicture']['value'][0]['id']==$resource['id'])
                        {
                            $isLeadPicture = 1;$itemLeadPicture = 1;
                        } elseif ($productItem['LeadPicture']['value'][1]['id']==$resource['id']) {
                            $isLeadPicture = 1;$itemLeadPicture = 1;
                        }
                    }elseif (count($productItem['LeadPicture']['value'])==1 && $productItem['LeadPicture']['value'][0]['id']==$resource['id']) {$isLeadPicture = 1;$itemLeadPicture = 1;}
                    $picturePath = $pictureBasePath . $resource['OriginalFilename']['value'];
                    if (file_exists($picturePath) && ($resource['OriginalFilename']['value'] != '')) {
                        try {
                            $this->_addProductImage($cProductId, $resource['OriginalFilename']['value'], $isLeadPicture);
                            $this->_logMessage('O', false);
                            fputcsv($this->_fileHandlerResources,array($cProductId, $resource['ResourceFileId']['value'], $resource['OriginalFilename']['value'], 1,$resource['ImageType']['value'], $isLeadPicture));

                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    } else {
                        $this->_logMessage('X', false);
                        fputcsv($this->_fileHandlerPictures,array($productData['StyleNbr']['value'],$productItem['CinoNumber']['value'],$resource['OriginalFilename']['value']?$resource['OriginalFilename']['value']:'empty OriginalFilename tag'));
                    }
                    $resourceList[$resource['id']] = $resource['OriginalFilename']['value'];
                }
                if (!$itemLeadPicture){
                    foreach ($productData['Resources']['value'] as $resource) {
                        $isLeadPicture = 0;
                        if (count($productItem['LeadPicture']['value'])==2 && $resource['ImageType']['value']=="packshots")
                        {
                            if ($productItem['LeadPicture']['value'][0]['id']==$resource['id'])
                            {
                                $isLeadPicture = 1;
                            } elseif ($productItem['LeadPicture']['value'][1]['id']==$resource['id']) {
                                $isLeadPicture = 1;
                            }
                        }elseif (count($productItem['LeadPicture']['value'])==1 && $productItem['LeadPicture']['value'][0]['id']==$resource['id']) {$isLeadPicture = 1;}
                        $picturePath = $pictureBasePath . $resource['OriginalFilename']['value'];
                        if ($isLeadPicture) {
                        if (file_exists($picturePath) && ($resource['OriginalFilename']['value'] != '')) {
                            try {
                                $this->_addProductImage($cProductId, $resource['OriginalFilename']['value'], $isLeadPicture);
                                $this->_logMessage('O', false);
                                fputcsv($this->_fileHandlerResources,array($cProductId, $resource['ResourceFileId']['value'], $resource['OriginalFilename']['value'], 1,$resource['ImageType']['value'], $isLeadPicture));

                            } catch (Exception $e) {
                                echo $e->getMessage();
                            }
                        } else {
                            $this->_logMessage('X', false);
                            fputcsv($this->_fileHandlerPictures,array($productData['StyleNbr']['value'],$productItem['CinoNumber']['value'],$resource['OriginalFilename']['value']?$resource['OriginalFilename']['value']:'empty OriginalFilename tag'));
                        }}
                        $resourceList[$resource['id']] = $resource['OriginalFilename']['value'];
                    }
                }


                // create cino pictures
                $this->_logMessage('Creating lead pictures files ');
                if (count($productItem['LeadPicture']['value']) == 2) {
                    $picture1Name = $resourceList[$productItem['LeadPicture']['value'][0]['id']];
                    $picture1Path = $pictureBasePath . $picture1Name;
                    $picture2Name = $resourceList[$productItem['LeadPicture']['value'][1]['id']];
                    $picture2Path = $pictureBasePath . $picture2Name;
                    // if first picture is Plus size it is copied to cino_p
                    if ($picture1Name[3] == 'P') {
                        if (file_exists($picture1Path)) copy($picture1Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '_p.jpg');
                        if (file_exists($picture2Path)) copy($picture2Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '.jpg');
                        $this->_logMessage('Pp', false);

                    } else {
                        if (file_exists($picture1Path)) copy($picture1Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '.jpg');
                        if (file_exists($picture2Path)) copy($picture2Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '_p.jpg');
                        $this->_logMessage('Pp', false);

                    }

                } elseif ($resourceList[$productItem['LeadPicture']['value'][0]['id']][3] == 'P') {
                    $picture1Name = $resourceList[$productItem['LeadPicture']['value'][0]['id']];
                    $picture1Path = $pictureBasePath . $picture1Name;

                    if (file_exists($picture1Path)) {
                        copy($picture1Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '.jpg');
                        copy($picture1Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '_p.jpg');
                        $this->_logMessage('Pp', false);
                    }
                } else {
                    $picture1Name = $resourceList[$productItem['LeadPicture']['value'][0]['id']];
                    $picture1Path = $pictureBasePath . $picture1Name;

                    if (file_exists($picture1Path)) {
                        copy($picture1Path, $cino_picture_directory . $productItem['CinoNumber']['value'] . '.jpg');
                        $this->_logMessage('P', false);
                    }
                }

                // end create cino picture

                // adding the different attribute values per store view
                $productShortDescriptionn = array();
                foreach ($productData['DescriptionCatalogues']['value'] as $key => $description){
                    $temp = explode('.',$description);
                    $productShortDescriptionn [$key] = $temp[0].'.';
                }


                $sql = "INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:short_descr_id,:store_id,:entity_id,:short_description)ON DUPLICATE KEY UPDATE `value` = :short_description;
                            INSERT INTO catalog_product_entity_text (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:descr_id,:store_id,:entity_id,:description)ON DUPLICATE KEY UPDATE `value` = :description;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:meta_descr_id,:store_id,:entity_id,:meta_description)ON DUPLICATE KEY UPDATE `value` = :meta_description;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:meta_title_id,:store_id,:entity_id,:meta_title)ON DUPLICATE KEY UPDATE `value` = :meta_title;
                           INSERT INTO catalog_product_entity_varchar (entity_type_id, attribute_id, store_id, entity_id, value) VALUES (:entity_type_id,:name_id,:store_id,:entity_id,:name)ON DUPLICATE KEY UPDATE `value` = :name;
                            ";

                if($this->_websiteStoreView['uk']) {
                    $binds = array(
                        'entity_type_id'    => $this->_productEntityTypeId,
                        'short_descr_id'    => $this->_shortDescriptionId,
                        'descr_id'          => $this->_descriptionId,
                        'meta_descr_id'     => $this->_metaDescriptionId,
                        'meta_title_id'     => $this->_metaTitleId,
                        'name_id'           => $this->_nameId,
                        'store_id'          => $this->_websiteStoreView['uk'],
                        'entity_id'         => $cProductId,
                        'short_description' => $productShortDescriptionn['en'],
                        'description'       => $productData['DescriptionCatalogues']['value']['en'],
                        'meta_description'  => $productShortDescriptionn['en'],
                        'meta_title'        => $productData['HeaderWebs']['value']['en'],
                        'name'              => $productData['HeaderWebs']['value']['en'],
                         );
                    $connW->query($sql, $binds);
                }

                if($this->_websiteStoreView['dk']) {
                    $binds = array(
                        'entity_type_id'    => $this->_productEntityTypeId,
                        'short_descr_id'    => $this->_shortDescriptionId,
                        'descr_id'          => $this->_descriptionId,
                        'meta_descr_id'     => $this->_metaDescriptionId,
                        'meta_title_id'     => $this->_metaTitleId,
                        'name_id'           => $this->_nameId,
                        'store_id'          => $this->_websiteStoreView['dk'],
                        'entity_id'         => $cProductId,
                        'short_description' => $productShortDescriptionn['da'],
                        'description'       => $productData['DescriptionCatalogues']['value']['da'],
                        'meta_description'  => $productShortDescriptionn['da'],
                        'meta_title'        => $productData['HeaderWebs']['value']['da'],
                        'name'              => $productData['HeaderWebs']['value']['da'],
                    );
                    $connW->query($sql, $binds);
                }

                if($this->_websiteStoreView['ch']) {
                    $binds = array(
                        'entity_type_id'    => $this->_productEntityTypeId,
                        'short_descr_id'    => $this->_shortDescriptionId,
                        'descr_id'          => $this->_descriptionId,
                        'meta_descr_id'     => $this->_metaDescriptionId,
                        'meta_title_id'     => $this->_metaTitleId,
                        'name_id'           => $this->_nameId,
                        'store_id'          => $this->_websiteStoreView['ch'],
                        'entity_id'         => $cProductId,
                        'short_description' => $productShortDescriptionn['de_CH'],
                        'description'       => $productData['DescriptionCatalogues']['value']['de_CH'],
                        'meta_description'  => $productShortDescriptionn['de_CH'],
                        'meta_title'        => $productData['HeaderWebs']['value']['de_CH'],
                        'name'              => $productData['HeaderWebs']['value']['de_CH'],
                    );
                    $connW->query($sql, $binds);
                }

                if($this->_websiteStoreView['de']) {
                    $binds = array(
                        'entity_type_id'    => $this->_productEntityTypeId,
                        'short_descr_id'    => $this->_shortDescriptionId,
                        'descr_id'          => $this->_descriptionId,
                        'meta_descr_id'     => $this->_metaDescriptionId,
                        'meta_title_id'     => $this->_metaTitleId,
                        'name_id'           => $this->_nameId,
                        'store_id'          => $this->_websiteStoreView['de'],
                        'entity_id'         => $cProductId,
                        'short_description' => $productShortDescriptionn['de'],
                        'description'       => $productData['DescriptionCatalogues']['value']['de'],
                        'meta_description'  => $productShortDescriptionn['de'],
                        'meta_title'        => $productData['HeaderWebs']['value']['de'],
                        'name'              => $productData['HeaderWebs']['value']['de'],
                    );
                    $connW->query($sql, $binds);
                }

                if($this->_websiteStoreView['nl']) {
                    $binds = array(
                        'entity_type_id'    => $this->_productEntityTypeId,
                        'short_descr_id'    => $this->_shortDescriptionId,
                        'descr_id'          => $this->_descriptionId,
                        'meta_descr_id'     => $this->_metaDescriptionId,
                        'meta_title_id'     => $this->_metaTitleId,
                        'name_id'           => $this->_nameId,
                        'store_id'          => $this->_websiteStoreView['nl'],
                        'entity_id'         => $cProductId,
                        'short_description' => $productShortDescriptionn['nl'],
                        'description'       => $productData['DescriptionCatalogues']['value']['nl'],
                        'meta_description'  => $productShortDescriptionn['nl'],
                        'meta_title'        => $productData['HeaderWebs']['value']['nl'],
                        'name'              => $productData['HeaderWebs']['value']['nl'],
                    );
                    $connW->query($sql, $binds);
                }

                if($this->_websiteStoreView['nl']) {
                    $binds = array(
                        'entity_type_id'    => $this->_productEntityTypeId,
                        'short_descr_id'    => $this->_shortDescriptionId,
                        'descr_id'          => $this->_descriptionId,
                        'meta_descr_id'     => $this->_metaDescriptionId,
                        'meta_title_id'     => $this->_metaTitleId,
                        'name_id'           => $this->_nameId,
                        'store_id'          => $this->_websiteStoreView['se'],
                        'entity_id'         => $cProductId,
                        'short_description' => $productShortDescriptionn['sv'],
                        'description'       => $productData['DescriptionCatalogues']['value']['sv'],
                        'meta_description'  => $productShortDescriptionn['sv'],
                        'meta_title'        => $productData['HeaderWebs']['value']['sv'],
                        'name'              => $productData['HeaderWebs']['value']['sv'],
                    );
                    $connW->query($sql, $binds);
                }

                $cProduct->clearInstance();
            } catch (Exception $e) {
                echo "item " . $productData['HeaderWebs']['value']['en'] . " not added\n";
                echo "exception:$e";
            }
        // adding style related products
            //$no_products = count($productItemIDs);
            //if ($no_products>1){
                //for ($i=0;$i<$no_products;$i++) {
                //
                //    $main_product_id = $productItemIDs[$i];
                //
                //    //get related products
                //    $prod = Mage::getModel('catalog/product');
                //    $prod->load($main_product_id);
                //
                //    $related_prods = $prod->getRelatedProductIds();
                //    $related_news  = array(); // store new related prods
                //
                //    for ($j=0;$j<$no_products;$j++) {
                //        if ($i == $j) continue;
                //
                //        if (!in_array($productItemIDs[$j], $related_prods)) {
                //            //Not related
                //            //echo "Add to related \n";
                //            $related_news[$productItemIDs[$j]] = array( "position"=> 0);
                //        }
                //    }
                //
                //    if (count($related_news)) {
                //        //Add related products and save
                //        $prod->setRelatedLinkData($related_news);
                //        #$prod->setUpSellLinkData($param);
                //        #$prod->setCrossSellLinkData($param);
                //        $prod->save();
                //    }
                //
                //}
            //}
        }
    }


    private function _extractConfiguration($node, &$productData)
    {
        foreach ($node as $element) {
            $key = $element->getName();

            $value = (string)$element->value;
            if (empty($value)) {
                $value = (string)$element;
                if (in_array($key, array('SizeGroup', 'AdCodes', 'Prices'))) {
                    $stringXML = new Varien_Simplexml_Element('<general_bracket>' . $value . '</general_bracket>'); //simplexml_load_string($value, null);
                    $value = array();
                    switch ($key) {
                        case 'SizeGroup':
                            $stringXML = $stringXML->SizeRange;
                            $value[$stringXML->getName()] = array(
                                'name' => $stringXML->getAttribute('name'),
                                'value' => (string)$stringXML
                            );
                            break;
                        case 'AdCodes':
                            foreach ($stringXML->AdCode as $subElement) {
                                $value[] = array(
                                    'catalogue' => $subElement->getAttribute('catalogue'),
                                    'value' => (string)$subElement,
                                    'key' => 'AdCode'
                                );
                            }
                            unset($subElement);
                            break;
                        case 'Prices':
                            foreach ($stringXML->Catalogue as $subElement) {
                                $subValue = array();
                                foreach ($subElement->Country as $country) {
                                    //$subValue[] = array(
                                    //    'code' => ,
                                    //    'currency' => $country->getAttribute('currency'),
                                    //    'size' => (string)$country->Size
                                    //);
                                    foreach ($country->Size as $sizePrice){
                                        $subValue[$sizePrice->getAttribute('name')][]=$country->getAttribute('code');
                                    }
                                }
                                $value['Catalogue'][] = array(
                                    'name' => $subElement->getAttribute('name'),
                                    'value' => $subValue
                                );
                            }
                            break;
                    }
                }
            } else {
                $value = null;
            }

            /*if(in_array($key, array('MeasureHeading', 'CatalogAdcode', 'ActiveCatalogue', 'Catalogue'))) {
                $value = null;
            }*/

            if (empty($value)) {
                foreach ($element as $subElement) {
                    $locale = $subElement->getAttribute('locale');
                    $text = (string)$subElement;
                    if ($locale) {
                        $value[$locale] = $text;
                        continue;
                    }

                    $id = $subElement->getAttribute('id');
                    if (!empty($id)) {
                        $order = $subElement->getAttribute('order');
                        $text = array(
                            'id' => $id,
                            'order' => $order
                        );
                    }

                    $value[] = $text;
                }
                unset($locale, $text, $order, $subElement);
            }

            if (in_array($key, array('Resources', 'Items'))) {
                $value = array();
                foreach ($element as $subElement) {
                    $subProductData = array();
                    $subProductData['id'] = $subElement->getAttribute('id');
                    $this->_extractConfiguration($subElement, $subProductData);
                    $value[] = $subProductData;
                }
                unset($subElement);
            }

            $finalValue['value'] = $value;
            $cvl = $element->getAttribute('cvl');
            if (!empty($cvl)) {
                $finalValue['cvl'] = $cvl;
            }

            $productData[$key] = $finalValue;
            unset($cvl, $finalValue);
        }
    }

    /**
     * Specific category functionality
     */
    public function start($options = array())
    {
//        $this->_logMessage('Starting product deleting');
//        $this->_deleteAllProducts();
//        exit;

        $this->_mediaGalleryId = $this->_getAttributeID('media_gallery');
        $this->_baseImageId = $this->_getAttributeID('image');
        $this->_smallImageId = $this->_getAttributeID('small_image');
        $this->_thumbnailId = $this->_getAttributeID('thumbnail');

        $this->_logMessage('Getting the pictures ready');
        $this->_getProductImageReady();
        $this->_logMessage('Finished');

        $this->_logMessage('Size parsing start');
        $this->_sizeTranslate = $this->_getSizeConfiguration();
        $this->_logMessage('Size parsing end');

        $this->_logMessage('Inventory parsing start');
        $this->_productInventory = $this->_getProductInventory();
        $this->_logMessage('Inventory end');


        $this->_customSizes = $this->_getCustomSize();
        $this->_getAttributeSetID('Default');
        $this->_attributeIdd = $this->_getAttributeID('bnp_size');
//        $this->_allWebsiteIDs = Mage::getModel('core/website')->getCollection()->getAllIds();

        $this->_allWebsiteIDs['base'] = Mage::getModel('core/website')->load('base')->getWebsiteId();
        $this->_allWebsiteIDs['uk'] = Mage::getModel('core/website')->load('uk')->getWebsiteId();
        $this->_allWebsiteIDs['dk'] = Mage::getModel('core/website')->load('dk')->getWebsiteId();
        $this->_allWebsiteIDs['se'] = Mage::getModel('core/website')->load('se')->getWebsiteId();
        $this->_allWebsiteIDs['de'] = Mage::getModel('core/website')->load('de')->getWebsiteId();
        $this->_allWebsiteIDs['ch'] = Mage::getModel('core/website')->load('ch')->getWebsiteId();
        $this->_allWebsiteIDs['nl'] = Mage::getModel('core/website')->load('nl')->getWebsiteId();


        $numberOfFiles = count($this->_data);
        $counter = 0;
        $this->_activeCatalogues = $this->_getActiveCatalogues();

        $this->_missingPictureFilePath = Mage::getBaseDir() . self::MISSING_PICTURES_BASE_PATH;

        $this->_fileHandlerPictures = fopen($this->_missingPictureFilePath, 'w');
        $this->_fileHandlerResources = fopen(Mage::getBaseDir() . self::RESOURCES_BASE_PATH, 'w');
        $this->_fileHandlerStyles = fopen(Mage::getBaseDir() . self::STYLES_BASE_PATH, 'w');

        foreach ($this->_data as $productConfig) {
            $toImport=0;
            $counter++;
            //	    if ($counter<3384) continue;
            $productData = array();
            $this->_extractConfiguration($productConfig->getNode(), $productData);
            //check if the product is in active catalogue
            foreach ($productData['Catalogue']['value'] as $productCatalog){
                if (in_array($productCatalog,$this->_activeCatalogues)){
                    $toImport = 1;
                }
            }
            if ($toImport){
                $this->_logMessage($counter . ' / ' . $numberOfFiles . ' - Adding product file');
                $this->_addProduct($productData);
            }else{
                $this->_logMessage($counter . ' / ' . $numberOfFiles . ' - Skipping product file');
            }
            if ($counter==3000) break;
        }
        fclose($this->_fileHandlerPictures);
        fclose($this->_fileHandlerResources);
        fclose($this->_fileHandlerStyles);






        $config  = Mage::getConfig()->getResourceConnectionConfig("default_setup");
        $this->_logMessage('Importing resources....');
        if ($fp = popen("mysql -u ".$config->username." -p".$config->password." ".$config->dbname." -e \"LOAD DATA LOCAL INFILE '".Mage::getBaseDir()."/dump_files/tmp_import_resources_part1.csv' REPLACE INTO TABLE bonaparte_resources FIELDS TERMINATED BY ',';\";", "r"))  {
            while( !feof($fp) ){
                echo fread($fp, 1024);
                flush();
            }
            fclose($fp);
            $this->_logMessage('Done!' . "\n");
        }
        $this->_logMessage('Importing styles....');
        if ($fp = popen("mysql -u ".$config->username." -p".$config->password." ".$config->dbname." -e \"LOAD DATA LOCAL INFILE '".Mage::getBaseDir()."/dump_files/tmp_import_styles_part1.csv' REPLACE INTO TABLE bonaparte_styles FIELDS TERMINATED BY ',';\";", "r"))  {
            $this->_logMessage('Done!' . "\n");
            while( !feof($fp) ){
                echo fread($fp, 1024);
                flush();
            }
            fclose($fp);
        }


//        $this->_logMessage('Creating STYLE product relations....');
//        $connW = Mage::getSingleton('core/resource')->getConnection('core_write');
//        $sql ="INSERT IGNORE INTO `catalog_product_link` (`product_id`, `linked_product_id`, `link_type_id`)
//                    SELECT
//                        T1.`configurable_entity_id`, T2.`configurable_entity_id`, C1.`link_type_id`
//                    FROM
//                        (SELECT DISTINCT `style`,`configurable_entity_id` FROM `bonaparte_styles` WHERE 1) T1,
//                        (SELECT DISTINCT `style`,`configurable_entity_id` FROM `bonaparte_styles` WHERE 1) T2,
//                        (SELECT `link_type_id` FROM `catalog_product_link_type` WHERE `code`='relation') C1
//                    WHERE
//                        T1.STYLE=T2.STYLE AND
//                        T1.configurable_entity_id<>T2.configurable_entity_id;";
//        $connW->query($sql);
//        $this->_logMessage('Done!' . "\n");


        $this->_logMessage('ALL DONE!!!' . "\n");
        $this->_logMessage('There were ' . $this->_newProductCounter . ' new products created!' . "\n");
    }

}