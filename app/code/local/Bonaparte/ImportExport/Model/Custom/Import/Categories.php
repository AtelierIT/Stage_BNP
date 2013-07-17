<?php

/**
 * Stores the business logic for the custom category import
 *
 * @category    Bonaparte
 * @package     Bonaparte_ImportExport
 * @author      Atelier IT Team <office@atelierit.ro>
 */
class Bonaparte_ImportExport_Model_Custom_Import_Categories extends Bonaparte_ImportExport_Model_Custom_Import_Abstract
{
    /**
     * Path at which the category configuration is found
     *
     * @var string
     */
    const CONFIGURATION_FILE_PATH = '/dump_files/structure.xml';

    /**
     * Language index that will be used to determine what label value to use
     *
     * @var int
     */
    private $_languageIndex = 0;

    /**
     * Languages used to create the root categories for each one
     *
     * @var array
     */
    private $_languages = array('uk', 'dk', 'se', 'nl', 'de', 'ch');

    /**
     * Construct import model
     */
    protected function _construct()
    {
        $this->_logMessage('Reading configuration');

        $this->_configurationFilePath = Mage::getBaseDir() . self::CONFIGURATION_FILE_PATH;
        $this->_initialize();

        $this->_data = array();

        $this->_extractNode($this->getConfig(), $this->_data);

        $this->_logMessage('Finished reading configuration');
    }

    /**
     * Recursive method to extract unknown levels of categories
     *
     * @param mixed (Varien_Simplexml_Config|Varien_Simplexml_Element) $node
     * @param array $categoryStructure
     */
    private function _extractNode($node, &$categoryStructure)
    {
        if ($node instanceof Varien_Simplexml_Config) {
            $folder = $node->getNode('Folder');
        } else {
            $folder = $node->Folder;
        }

        if (empty($folder)) {
            return;
        }

        foreach ($folder as $node) {
            $attributeId = $node->getAttribute('groupId');
            $name = (array)$node->locale;
            $categoryStructure[$attributeId] = array(
                'name' => $name['value'],
                'children' => array()
            );

            $this->_extractNode($node, $categoryStructure[$attributeId]['children']);
        }
    }

    /**
     * Recursive method to add unknown levels of categories
     *
     * @param mixed (integer|Mage_Catalog_Model_Category) $parentId
     * @param array $children
     */
    private function _addCategory($parent, $children)
    {
        $parentId = $parent->getId();
        foreach ($children as $oldCategoryId => $data) {
            $category = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToFilter('old_id', $oldCategoryId)
                ->addAttributeToFilter('parent_id', $parentId)
                ->load()
                ->getFirstItem();

            $categoryData = array(
                'name' => (is_array($data['name'])) ? $data['name'][$this->_languageIndex] : $data['name'],
                'is_active' => 1,
                'include_in_menu' => 1,
                'is_anchor' => 1,
                'url_key' => '',
                'description' => 'parent is ' . $parentId
            );

            $actionLabel = 'Updating';
            if(!$category->getId()) {
                $parent->setPathIds(null);
                // Create category object
                $category = Mage::getModel('catalog/category')
                    ->setStoreId(0)
                    ->setPath(implode('/', $parent->getPathIds()))
                    ->setParentId($parentId)
                    ->setAttributeSetId($category->getDefaultAttributeSetId());

                $actionLabel = 'Adding';
                $categoryData['old_id'] = $oldCategoryId;
            }

            $this->_logMessage(
                $actionLabel
                    . ' category '
                    . $categoryData['name'] . ' for ' . $this->_languages[$this->_languageIndex] . ' root category'
            );

            $category->addData($categoryData)->save();

            $parent->clearInstance();

            if (!empty($data['children'])) {
                $this->_addCategory($category, $data['children']);
            }
        }
    }

    /**
     * Remove category duplicates
     *
     * @param array $children
     */
    private function _removeDuplicates($children)
    {
        foreach ($children as $externalCategoryId => $data) {
            if (!empty($data['children'])) {
                $this->_removeDuplicates($data['children']);
            }

            $this->_removeCategory($externalCategoryId, 'old_id');
        }
    }

    /**
     * Remove category
     *
     * @param string $id
     * @param string $field
     *
     * @return void
     */
    private function _removeCategory($id, $field) {
        $categoryCollection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToFilter($field, $id)
            ->load();

        if(!$categoryCollection->count()) {
            return;
        }

        $this->_logMessage('Removing category with ' . $field . ': ' . $id);

        foreach ($categoryCollection as $duplicateCategory) {
            try {
                $duplicateCategory->delete();
            } catch(Exception $e) {
                $this->_logMessage('Can\'t remove ' . $id . ' because ' . $e->getMessage());
            }

            $duplicateCategory->clearInstance();
            echo '.';
        }
    }

    /**
     * Specific category functionality
     *
     * @param array $options
     */
    public function start($options = array())
    {
        $this->_logMessage('Started importing categories');
        $startTime = time();
        if($options['remove_categories_with_identical_code']) {
            // before importing remove last imported categories
            $this->_logMessage('Removing child categories');
            $this->_removeDuplicates($this->_data);
            $this->_logMessage('Finished removing child categories');
        }

        foreach ($this->_languages as $languageIndex => $language) {
            $name = $language . ' root category';

            if($options['remove_categories_with_identical_code']) {
                // also remove all language root categories
                $this->_removeCategory($name, 'name');
            }

            $this->_languageIndex = $languageIndex;

            $category = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToFilter('name', $name)
                ->load()
                ->getFirstItem();

            if(!$category->getId()) {
                // Create category object
                $category = Mage::getModel('catalog/category')
                    ->setStoreId(0)
                    ->setParentId('1')
                    ->setPath('1');
                $category->setAttributeSetId($category->getDefaultAttributeSetId());
            }

            $category->addData(array(
                    'name' => $name,
                    'description' => 'Category ' . $language,
                    'meta_title' => 'Meta Title',
                    'meta_keywords' => 'Meta Keywords',
                    'meta_description' => 'Meta Description',
                    'display_mode' => 'PRODUCTS',
                    'is_active' => 1,
                    'is_anchor' => 1
                ));

            try {
                $this->_logMessage('Creating ' . $name);
                $category->save();
                $this->_logMessage('Done');
            } catch (Exception $e) {
                $this->_logMessage('Error while creating ' . $name . ':' . $e->getMessage());
                exit;
            }

            $this->_logMessage('Adding categories for ' . $name);
            $this->_addCategory($category, $this->_data);
        }

        $this->_logExecutionTime();
        $this->_logMemoryUsed();
        $this->_logMessage('Import finished!' . "\n");
    }

}
