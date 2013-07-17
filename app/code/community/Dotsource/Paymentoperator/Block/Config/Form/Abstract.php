<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Config_Form_Abstract
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    /**
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::addColumn()
     *
     * @param string $name
     * @param array $params
     */
    public function addColumn($name, $params)
    {
        //check for multi html elements in one column
        if ($this->_hasMultipleElementsInColumn($params)) {
            $multipleElements = array();

            foreach ($params as $columnPart) {
                if (is_array($columnPart)) {
                    $this->addColumn($name, $columnPart);
                    $multipleElements[] = $this->_columns[$name];
                    unset($this->_columns[$name]);
                }
            }

            //Set the column attributes
            foreach ($params as $key => $value) {
                if ($this->_isColumnAttribute($key)) {
                    $multipleElements[$key] = $value;
                }
            }

            //Set the multiple columns
            $this->_columns[$name] = $multipleElements;

            return;
        }


        //First do the parent stuff
        parent::addColumn($name, $params);

        $hasInputType       = array_key_exists('input-type', $params) && !empty($params['input-type']);
        $hasValidation      = array_key_exists('validate', $params) && !empty($params['validate']);
        $hasMultipleSelect  = array_key_exists('multiple-select', $params) && is_bool($params['multiple-select']);
        $hasValues          = array_key_exists('values', $params)
                                && !empty($params['values'])
                                && is_array($params['values']);
        //Content before or after the element
        $hasContentBefor    = array_key_exists('content-before-element', $params) && !empty($params['content-before-element']);
        $hasContentAfter    = array_key_exists('content-after-element', $params) && !empty($params['content-after-element']);

        //Check for column title
        $hasTitle           = array_key_exists('title', $params) && !empty($params['title']);

        //Check for an other name for the form element
        $hasName            = array_key_exists('name', $params) && !empty($params['name']);

        //Join the old an the new settings
        $this->_columns[$name] = array_merge($this->_columns[$name], array(
            'input-type'                => ($hasInputType)? $params['input-type'] : 'text',
            'multiple-select'           => ($hasMultipleSelect)? $params['multiple-select'] : false,
            'values'                    => ($hasValues)? $params['values'] : array(),
            'validate'                  => ($hasValidation)?  $params['validate'] : '',
            'content-before-element'    => ($hasContentBefor)? $params['content-before-element'] : '',
            'content-after-element'     => ($hasContentAfter)? $params['content-after-element'] : '',
            'title'                     => ($hasTitle)? $params['title'] : '',
            'name'                      => ($hasName)? $params['name'] : '',
        ));
    }


    /**
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract::_renderCellTemplate()
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }

        $column     = $this->_columns[$columnName];

        //Check for multi html elements in one column
        if ($this->_hasMultipleElementsInColumn($column)) {
            $defaultColumn  = $column;
            $htmlCode       = '';

            foreach ($column as $columnPart) {
                if (is_array($columnPart)) {
                    $this->_columns[$columnName] = $columnPart;
                    $htmlCode .= $this->_renderCellTemplate($columnName);
                    unset($this->_columns[$columnName]);
                }
            }

            //Set the multiple columns
            $this->_columns[$columnName] = $defaultColumn;
            return $htmlCode;
        }

        //Check for custom name
        if (array_key_exists('name', $column) && !empty($column['name'])) {
            $columnName = $column['name'];
        }

        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if (array_key_exists('renderer', $column) && $column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }

        //Build the input
        $input = "";

        if (array_key_exists('content-before-element', $column)) {
            $input  = $column['content-before-element'];
        }

        $input  .= '<input name="'. $inputName .'" value="#{'. $columnName .'}"';

        //Set type
        if (array_key_exists('input-type', $column) && !empty($column['input-type'])) {
            $input  .= ' type="'. $column['input-type'] .'" ';
        }

        //Set size
        if (array_key_exists('size', $column) && !empty($column['size'])) {
            $input  .= ' size="'. $column['size'] .'" ';
        }

        $hasClass       = array_key_exists('class', $column) && !empty($column['class']);
        $hasValidation  = array_key_exists('validate', $column) && !empty($column['validate']);

        //Start class tag
        $input  .= ' class="';

        //Add the class
        if ($hasClass) {
            $input  .= " {$column['class']} ";
        } else {
            //Add default
            $input  .= ' input-text ';
        }

        //Add validation
        if ($hasValidation) {
            $input  .= " {$column['validate']} ";
        }

        //End class
        $input  .= '" ';

        //Add style
        if (array_key_exists('style', $column) && !empty($column['style'])) {
            $input  .= ' style="'. $column['style'] .'"';
        }

        //End input
        $input  .= ' />';

        //Add the after html
        if (array_key_exists('content-after-element', $column)) {
            $input .= $column['content-after-element'];
        }

        //Return the input
        return $input;
    }


    /**
     * Check if the given $column has multiple html elements.
     *
     * @param array $columnName
     */
    protected function _hasMultipleElementsInColumn($column)
    {
        //check if we can work with the given data
        if (!is_array($column) || empty($column)) {
            return false;
        }

        //If all children an array we have multiple elements
        foreach ($column as $key => $testColumn) {
            if ((!$this->_isColumnAttribute($key) && !is_array($testColumn))) {
                return false;
            }
        }

        return true;
    }


    /**
     * Check if the given key is an column attribute.
     *
     * @param string $key
     * @return boolean
     */
    protected function _isColumnAttribute($key)
    {
        $attributes = array('label', 'title');

        return in_array($key, $attributes);
    }


    /**
     * Return all elements from the form.
     *
     * @param array $columns
     * @param array $collectedElements
     * @return array
     */
    public function getAllFormElements(array $columns = array())
    {
        //Element for collecting all html elements
        if (empty($columns)) {
            $columns    = $this->_columns;
        }

        $collectedElements = array();

        //Iterate over all columns
        foreach ($columns as $column) {
            //Check for multi elements in column
            if ($this->_hasMultipleElementsInColumn($column)) {
                foreach ($column as $columnPart) {
                    if (is_array($columnPart) && !empty($columnPart)) {
                        $collectedElements = array_merge($collectedElements, $this->getAllFormElements(array($columnPart)));
                    }
                }
            } else {
                //Normal element
                $collectedElements[] = $column;
            }
        }

        return $collectedElements;
    }


    /**
     * Return the paymentoperator helper
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}