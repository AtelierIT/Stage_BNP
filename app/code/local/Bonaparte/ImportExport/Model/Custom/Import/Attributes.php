<?php

/**
 * Stores the business logic for the custom attribute import
 *
 * Attribute problems:
 *
 * Code: AdCodes OK
 * Code: Color OK
 * Code: Size
 *      88113 has no actual size
 *
 * @category    Bonaparte
 * @package     Bonaparte_ImportExport
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_ImportExport_Model_Custom_Import_Attributes extends Bonaparte_ImportExport_Model_Custom_Import_Abstract
{
    /**
     * Path at which the category configuration is found
     *
     * @var string
     */
    const CONFIGURATION_FILE_PATH = '/dump_files/xml/Cvl.xml';

    /**
     * Path at which the size attribute is found
     *
     * @var string
     */
    const CONFIGURATION_FILE_PATH_SIZE = '/dump_files/xml/size.csv';

    /**
     * Prefix used to distinguish the Magento core attributes from the Bonaparte attributes
     */
    const ATTRIBUTE_PREFIX = 'bnp_';

    /**
     * Attribute frontend input type select
     *
     * @var string
     */
    const ATTRIBUTE_FRONTEND_INPUT_SELECT = 'select';

    /**
     * Attribute frontend input type multiselect
     *
     * @var string
     */
    const ATTRIBUTE_FRONTEND_INPUT_MULTISELECT = 'multiselect';

    /**
     * Attribute frontend input type boolean
     *
     * @var string
     */
    const ATTRIBUTE_FRONTEND_INPUT_BOOLEAN = 'boolean';

    /**
     * Attribute frontend input type text
     *
     * @var string
     */
    const ATTRIBUTE_FRONTEND_INPUT_TEXT = 'text';
    /**
     *
     * Attribute frontend input type integer
     *
     * @var string
     */
    const ATTRIBUTE_FRONTEND_INPUT_DROPDOWN = 'select';

    /**
     * Name of the default attribute set
     *
     * @var string
     */
    const ATTRIBUTE_SET_DEFAULT = 'Default';

    /**
     * Name of the general attribute set group
     *
     * @var string
     */
    const ATTRIBUTE_SET_GROUP_GENERAL = 'General';

    /**
     * Prefix used in setting the options for a attribute
     *
     * @var string
     */
    const OPTION_KEY_PREFIX = 'option';

    /**
     * Codes retrieved from xml configuration for the attribute catalog adcode
     *
     * @var string
     */
    const ATTRIBUTE_EXTERNAL_CODE_CATALOG_ADCODE = 'CatalogAdcode';

    /**
     * Codes retrieved from xml configuration for the attribute size
     *
     * @var string
     */
    const ATTRIBUTE_EXTERNAL_CODE_SIZE = 'Size';

    /**
     * Separator used between the option id and option value when needed
     *
     * @var string
     */
    const OPTION_ID_VALUE_SEPARATOR = '_';

    /**
     * Custom attribute codes
     *
     * @var string
     */
    const CUSTOM_ATTRIBUTE_CODE_COLOR = 'bnp_color';
    const CUSTOM_ATTRIBUTE_CODE_FITTING = 'bnp_fitting';
    const CUSTOM_ATTRIBUTE_CODE_COMPOSITION = 'bnp_composition';
    const CUSTOM_ATTRIBUTE_CODE_CONCEPT = 'bnp_concept';
    const CUSTOM_ATTRIBUTE_CODE_PROGRAM = 'bnp_program';
    const CUSTOM_ATTRIBUTE_CODE_PRODUCT_MAIN_GROUP = 'bnp_productmaingroup';
    const CUSTOM_ATTRIBUTE_CODE_PRODUCT_GROUP = 'bnp_productgroup';
    const CUSTOM_ATTRIBUTE_CODE_PRODUCT_SUB_GROUP = 'bnp_productsubgroup';
    const CUSTOM_ATTRIBUTE_CODE_CATALOGUE = 'bnp_catalogue';
    const CUSTOM_ATTRIBUTE_CODE_SEASON = 'bnp_season';
    const CUSTOM_ATTRIBUTE_CODE_WASH_ICON = 'bnp_washicon';
    const CUSTOM_ATTRIBUTE_CODE_STYLE_NBR = 'bnp_stylenbr';
    const CUSTOM_ATTRIBUTE_CODE_SIZE_TRANSLATE = 'bnp_sizetranslate';
    const CUSTOM_ATTRIBUTE_CODE_PRICE_CATALOGUE = 'bnp_pricecatalogue';
    const CUSTOM_ATTRIBUTE_CODE_TRAFFIC_LIGHT = 'bnp_trafficlight';



    /**
     * Attributes that have a specific frontend input different than "select"
     *
     * @var array
     */
    private $_attributeCodesFrontendInput = array(
        self::ATTRIBUTE_FRONTEND_INPUT_MULTISELECT => array('Catalogue', 'Season', 'WashIcon', 'AdCodes'),
        self::ATTRIBUTE_FRONTEND_INPUT_BOOLEAN => array('AnimalOrigin', 'DisplayComposition'),
        self::ATTRIBUTE_FRONTEND_INPUT_TEXT => array('MeasurementChart','MeasureChartAbrv','SizeTranslate'),
        self::ATTRIBUTE_FRONTEND_INPUT_DROPDOWN => array('StyleNbr','TrafficLight'));

    /**
     * Maps the website code the its store view id
     *
     * @var array
     */
    private $_websiteStoreView = array();

    /**
     * Contains all attributes that need to be set as configurable
     *
     * @var array
     */
    private $_configurableAttributes = array('Size');

    /**
     * Contains all attributes that need to be set as searchable/Use In Layered Navigation
     *
     * @var array
     */
    private $_searchableAttributes = array('ColorGroup');

    /**
     * Contains all attributes that need to be set on website scope
     *
     * @var array
     */
    private $_websiteAttributes = array('SizeTranslate');

    /**
     * Remove previously imported attributes
     */
    private function _removeAttributesWithIdenticalAttributeCode()
    {
        $this->_logMessage('Started removing duplicate attributes');

        $currentAttributeNumber = 0;
        $attributesNumber = count($this->_data);
        foreach ($this->_data as $attributeCode => $attributeConfigurationData) {
            $currentAttributeNumber++;
            $magentoAttributeCode = self::ATTRIBUTE_PREFIX . strtolower($attributeCode);
            $attributeCollection = Mage::getModel('eav/entity_attribute')->getCollection()
                ->setModel('catalog/resource_eav_attribute')
                ->addFieldToFilter('attribute_code', $magentoAttributeCode)
                ->load();

            foreach ($attributeCollection as $duplicateAttribute) {
                $this->_logMessage(
                    'Deleting attribute '
                        . $currentAttributeNumber
                        . ' out of '
                        . $attributesNumber
                        . ' with the code '
                        . '"'
                        . $magentoAttributeCode
                        . '"'
                );

                $duplicateAttribute->delete();
                $duplicateAttribute->clearInstance();
            }
            unset($attributeCollection);
        }

        $this->_removeExternalIdsWithoutInternalId();

        $this->_logMessage('Finished removing duplicate attributes');
    }

    /**
     * Remove relations with external ids if there is none anymore after the attribute has been deleted
     */
    private function _removeExternalIdsWithoutInternalId() {
        $this->_logMessage('Started removing obsolete external id internal id relations');
        $read = Mage::getSingleton('core/resource')->getConnection('core_write');
        $result = $read->query('SELECT bierao.id FROM bonaparte_importexport_external_relation_attribute_option bierao
	                            LEFT JOIN eav_attribute_option eao ON bierao.internal_id = eao.option_id
	                            WHERE eao.option_id IS NULL OR !bierao.external_id');

        $relationIds = array();
        while($row = $result->fetch()) {
            $relationIds[] = $row['id'];
        }

        if(empty($relationIds)) {
            $this->_logMessage('No obsolete external id internal id relations found');
            return;
        }

        $relationIds = implode(',', $relationIds);
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->query('DELETE FROM bonaparte_importexport_external_relation_attribute_option
                        WHERE id IN (' . $relationIds . ')');

        $this->_logMessage('Finished removing obsolete external id internal id relations');
    }

    private function _addMissingAttributes() {
        $this->_logMessage('Adding missing attributes');

        $missingAttributes = array(
            'AnimalOrigin' => array(0, 1), // bolean
            'DisplayComposition' => array(0, 1), // boolean
            'ColorGroup' => array('White', 'Sand', 'Red', 'Yellow', 'Blue', 'Green', 'Purple', 'Brown', 'Grey', 'Black'), // single value
            'AdCodes' => array(), // multiple values
            'StyleNbr' => array(), // text, needs values
            'SizeTranslate' => array(), // text, needs values
            'MeasurementChart' => array(), // text, needs values
            'MeasureChartAbrv' => array(), // text, needs values
            'Length' => array(), // single value, needs values
            'WashInstructions' => array(), // single value, needs values
            'TrafficLight' => array(1,2,3,4), // single value
            'PriceCatalogue' => array()//single value
        );

        foreach($this->_data['CatalogAdcode'] as $catalogAdcode) {
            $missingAttributes['AdCodes'][$catalogAdcode] = $catalogAdcode;
        }

        $this->_data = array_merge($this->_data, $missingAttributes);
        $this->_data['PriceCatalogAdcode'] = $this->_data['CatalogAdcode'];
    }

    /**
     * Retrieve frontend input type
     *
     * @param $attributeCode
     *
     * @return string
     */
    private function _getFrontendInput($attributeCode) {
        foreach($this->_attributeCodesFrontendInput as $frontendInput => $attributeCodes) {
            if(in_array($attributeCode, $attributeCodes)) {
                return $frontendInput;
            }
        }

        return self::ATTRIBUTE_FRONTEND_INPUT_SELECT;
    }

    /**
     * Add attribute to a specific attribute set in this case "General"
     *
     * @param $magentoAttributeCode
     */
    private function _addAttributeToSet($magentoAttributeCode) {
        $model = Mage::getModel('eav/entity_setup', 'core_setup');
        $attribute = $model->getAttribute('catalog_product', $magentoAttributeCode);
        $attributeSetId = $model->getAttributeSetId('catalog_product', self::ATTRIBUTE_SET_DEFAULT);
        $attributeGroup = $model->getAttributeGroup(
            'catalog_product',
            $attributeSetId,
            self::ATTRIBUTE_SET_GROUP_GENERAL
        );

        //add attribute to a set
        $model->addAttributeToSet(
            'catalog_product', $attributeSetId, $attributeGroup['attribute_group_id'], $attribute['attribute_id']
        );

        unset($model);
    }

    /**
     * Create relation between external id options and internal id options
     *
     * @param array $databaseOptions
     * @param array $externalOptionIds
     * @param array $optionLabels
     * @param $attributeCode
     *
     */
    private function _createAttributeOptionExternalInternalIdRelation($databaseOptions, $externalOptionIds, $optionLabels, $attributeCode) {
        $this->_logMessage('Relating external id of options to the internal id of options');

        $labelValueDatabaseOptions = array();
        foreach ($databaseOptions as $option) {
            $labelValueDatabaseOptions[$option['label']] = $option['value'];
        }

        foreach ($externalOptionIds as $externalOptionId) {
            $relationCollection = Mage::getModel('Bonaparte_ImportExport/External_Relation_Attribute_Option')
                ->getCollection()
                ->addFieldToFilter('attribute_code', $attributeCode)
                ->addFieldToFilter('external_id', $externalOptionId);
            $relationModel = $relationCollection->load()->getFirstItem();
            $relationId = $relationModel->getId();
            $relationModel->clearInstance();
            unset($relationModel, $relationCollection);

            if($relationId) {
                continue;
            }

            $internalId = $labelValueDatabaseOptions[$externalOptionId];
            if(empty($internalId) || in_array($attributeCode, array('bnp_adcodes'))) {
                $internalId = $labelValueDatabaseOptions[$optionLabels[$externalOptionId]];
            }

            // skip relation creation if there is no internal id
            if(empty($internalId)) {
                continue;
            }

            Mage::getModel('Bonaparte_ImportExport/External_Relation_Attribute_Option')
                ->setType(Bonaparte_ImportExport_Model_External_Relation_Attribute_Option::TYPE_ATTRIBUTE_OPTION)
                ->setExternalId($externalOptionId)
                ->setInternalId($internalId)
                ->setAttributeCode($attributeCode)
                ->save()
                ->clearInstance();
        }

        $this->_logMessage('Finished relating external id of options to the internal id of options');
    }

    /**
     * Returns new options for the attribute
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $model
     * @param array $attributeData
     *
     * @return array
     */
    private function _extractNewOptions($model, $attributeData) {
        $options = $model->getSource()->getAllOptions(false);

        $optionLabels = array();
        foreach($options as $option) {
            $optionLabels[$option['label']] = true;
        }
        unset($option);

        $newOptions = array();
        foreach($attributeData['option']['value'] as $key => $option) {
            if(isset($optionLabels[$option[0]])) {
                continue;
            }
            $newOptions[$key] = $option;
        }
        unset($key, $option);

        return $newOptions;
    }

    /**
     * Initialize store views by mapping website code to the store view id of the website
     */
    protected function _initialize() {
        parent::_initialize();

        foreach(Mage::app()->getWebsites() as $website) {
            $this->_websiteStoreView[strtolower($website->getCode())] = array_pop($website->getStoreIds());
        }
    }

    /**
     * Construct import model
     */
    protected function _construct()
    {
        $this->_logMessage('Reading configuration files');
        $this->_configurationFilePath = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH;
        $this->_initialize();

        $attributes = array();
        $attributesConfig = $this->getConfig()->getNode('group');
        foreach ($attributesConfig as $attribute) {
            $attributeCode = $attribute->getAttribute('id');
            $attributes[$attributeCode] = array();
            foreach ($attribute->cvl as $attributeValue) {
                if (!$attributeValue->values->value) {
                    $attributes[$attributeCode][$attributeValue->getAttribute('id')] = (string)$attributeValue->values;
                } else {
                    $nrValues = count($attributeValue->values->value);
                    for ($i = 0; $i < $nrValues; $i++) {
                        $attributes[$attributeCode][$attributeValue->getAttribute('id')][] = (string)$attributeValue->values->value[$i];
                    }
                }
            }
        }

        $this->_data = $attributes;

        $sizes = array();
        $handle = fopen(Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH_SIZE, 'r');
        while ($row = fgetcsv($handle,null,';','"')) {
            $sizes[] = $row[6];
        }
        $sizes = array_unique($sizes);
        fclose($handle);

        $this->_data['Size'] = $sizes;

        $this->_addMissingAttributes();

        $this->_logMessage('Finished reading configuration files');
    }

    /**
     * Specific category functionality
     *
     * @param array $options
     *
     * @return void
     */
    public function start($options = array())
    {
        if($options['remove_attributes_with_identical_attribute_code']) {
            // remove attributes with the same code
            $this->_removeAttributesWithIdenticalAttributeCode();
        }

        $this->_logMessage('Started importing all attributes');

        $currentAttributeNumber = 0;
        $attributesNumber = count($this->_data);
        $eavEntityCatalogProductTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        foreach ($this->_data as $attributeCode => $attributeConfigurationData) {
            $currentAttributeNumber++;

            // assemble attribute code
            $magentoAttributeCode = self::ATTRIBUTE_PREFIX . strtolower($attributeCode);

            // logs
            $this->_logMessage(
                'Processing attribute ' . $currentAttributeNumber . ' out of ' . $attributesNumber . ' with the code '
                    . '"' . $magentoAttributeCode . '"'
            );
            $this->_logMemoryUsage();
            $this->_logMessage('Adding attribute options(' . count($attributeConfigurationData) . ')');

            $counter = 0;
            $optionLabels = $optionValues = $optionIds = array();
            foreach ($attributeConfigurationData as $optionId => $optionValue) {
                $this->_logMessage('.', false);

                // add the store id in the label value array
                if (is_array($optionValue)) {
                    $optionValueCounter = 0;
                    $optionValues[self::OPTION_KEY_PREFIX . $counter][0] = $optionValue[2];
                    foreach($this->_websiteStoreView as $storeId) {
                        $optionValues[self::OPTION_KEY_PREFIX . $counter][$storeId] = $optionValue[$optionValueCounter];
                        $optionValueCounter++;
                    }
                } else {
                    // add option id to the label if the attribute code is CatalogueAdCode
                    if ((strlen($optionValue) <= 2) && $attributeCode == self::ATTRIBUTE_EXTERNAL_CODE_CATALOG_ADCODE) {
                        $optionId = $optionValue = $optionId . self::OPTION_ID_VALUE_SEPARATOR . $optionValue;
                    }

                    $optionValues[self::OPTION_KEY_PREFIX . $counter][0] = $optionValue;
                    foreach($this->_websiteStoreView as $storeId) {
                        $optionValues[self::OPTION_KEY_PREFIX . $counter][$storeId] = $optionValue;
                    }
                }

                $optionValue = (is_array($optionValue))?$optionValue[2]:$optionValue;

                if($attributeCode == self::ATTRIBUTE_EXTERNAL_CODE_SIZE) {
                    $optionId = $optionValue;
                }

                $optionIds[] = $optionId;
                $optionLabels[$optionId] = $optionValue;
                $counter++;
            }

            $attributeData = array(
                'attribute_code' => $magentoAttributeCode,
                'is_global' => (in_array($attributeCode,$this->_websiteAttributes))?Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE:Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
                'frontend_input' => $this->_getFrontendInput($attributeCode),
                'option' => array(
                    'value' => $optionValues
                ),
                'is_configurable' => (in_array($attributeCode,$this->_configurableAttributes))?1:0,
                'external_id' => $attributeCode,
                'frontend_label' => array('Bnp attribute ' . $attributeCode),
                'entity_type_id' => $eavEntityCatalogProductTypeId,
                'apply_to' => array('simple', 'configurable'),
                'default_value_text' => '',
                'default_value_yesno' => '0',
                'default_value_date' => '',
                'default_value_textarea' => '',
                'is_html_allowed_on_front' => '1',
                'is_user_defined' => '1',
                'is_unique' => '0',
                'is_required' => '0',
                'is_searchable' => (in_array($attributeCode,$this->_searchableAttributes))?1:0,
                'is_visible_in_advanced_search' => (in_array($attributeCode,$this->_searchableAttributes))?1:0,
                'is_comparable' => (in_array($attributeCode,$this->_searchableAttributes))?1:0,
                'is_used_for_price_rules' => '0',
                'is_wysiwyg_enabled' => '0',
                'is_visible_on_front' => '0',
                'is_filterable' => (in_array($attributeCode,$this->_searchableAttributes))?1:0,
                'is_filterable_in_search' => '0',
                'used_in_product_listing' => '0',
                'used_for_sort_by' => '0'
            );
            
            $attributeCreated = false;
            $model = Mage::getModel('catalog/resource_eav_attribute')->load($attributeCode, 'external_id');
            if(!$model->getId()) {
                $model->clearInstance();
                $model = Mage::getModel('catalog/resource_eav_attribute');
                $attributeData['backend_type'] = $model->getBackendTypeByInput($attributeData['frontend_input']);
                $attributeCreated = true;

                $this->_logMessage('Creating attribute: ' . $attributeCode);
            } else {
                $newOptions = $this->_extractNewOptions($model, $attributeData);
                $attributeData = array();
                $attributeData['option']['value'] = $newOptions;

                $this->_logMessage('Updating options for attribute: ' . $attributeCode);
            }

            $model->addData($attributeData);

            try {
                $this->_logMessage('Saving attribute');
                $model->save();
                $this->_logMessage('Saved');
            } catch (Exception $e) {
                $this->_logMessage(
                    'Sorry, error occured while trying to save the attribute "' . $attributeCode . '". Error: ' . $e->getMessage()
                );
            }

            $this->_createAttributeOptionExternalInternalIdRelation(
                $model->getSource()->getAllOptions(false), $optionIds, $optionLabels, $magentoAttributeCode
            );
            $model->clearInstance();

            if($attributeCreated) {
                $this->_addAttributeToSet($magentoAttributeCode);
            }
        }

        $this->_logMessage('Finished importing attributes' . "\n" );
    }

}
