<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Config_Form_Renderer_Select
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * @see Mage_Core_Block_Template::_toHtml()
     *
     * @return string
     */
    protected function _toHtml()
    {
        $select             = '';
        $column             = $this->getColumn();

        //check for content before html element
        if (array_key_exists('content-before-element', $column) && !empty($column['content-before-element'])) {
            $select .= $column['content-before-element'];
        }

        //Is multiple select?
        if ($this->isMultipleSelect()) {
            $select .= '<select name="'. $this->getInputName() .'[]" ';
        } else {
            $select .= '<select name="'. $this->getInputName() .'" ';
        }


        //Multiple select?
        if ($this->isMultipleSelect()) {
            $select  .= ' multiple="multiple"';
        }

        //Set size
        if (array_key_exists('size', $column) && !empty($column['size'])) {
            $select  .= ' size="'. $column['size'] .'" ';
        }

        $hasClass       = array_key_exists('class', $column) && !empty($column['class']);
        $hasValidation  = array_key_exists('validate', $column) && !empty($column['validate']);

        //Start class tag
        $select  .= ' class="';

        //Add the class
        if ($hasClass) {
            $select  .= " {$column['class']} ";
        } else {
            //Add default
            $select  .= ' input-text ';
        }

        //Add validation
        if ($hasValidation) {
            $select  .= " {$column['validate']} ";
        }

        //End class
        $select  .= '" ';

        //Add style
        if (array_key_exists('style', $column) && !empty($column['style'])) {
            $select  .= ' style="'. $column['style'] .'"';
        }

        //End the select
        $select   .= '>';

        //Build the options
        if (array_key_exists('values', $column)
            && !empty($column['values'])
            && is_array($column['values'])
        ) {
            foreach ($column['values'] as $country) {
                $select   .= '<option value="'.$country['value'].'">'.$country['label'].'</option>';
            }
        }

        $select   .= '</select>';

        if (array_key_exists('content-after-element', $column) && !empty($column['content-after-element'])) {
            $select .= $column['content-after-element'];
        }

        return $select;
    }


    /**
     * Return true is the select is an multiple select box.
     *
     * @return boolean
     */
    public function isMultipleSelect()
    {
        //Get the column
        $column = $this->getColumn();

        //Get the result
        return array_key_exists('multiple-select', $column)
            && is_bool($column['multiple-select'])
            && $column['multiple-select'];
    }


    /**
     * Check if the select box has a class.
     *
     * @return boolean
     */
    public function hasClass()
    {
        //Get the column
        $column = $this->getColumn();

        //Build the result
        return array_key_exists('class', $column)
            && !empty($column['class']);
    }


    /**
     * Return javascript code which call when a new element add to the form.
     *
     * @return string
     */
    public function getCodeForAddActionFormManipulation()
    {
        $column     = $this->getColumn();
        $columnName = $this->getColumnName();
        $js         = '';

        if ($this->isMultipleSelect()) {
            $js .=  "var tmpObject = arrayToObject(templateData.$columnName);";
        }

        //Select the operator
        $js     .=  "$(templateData._id).select('.{$column['class']} option').each(function(option) {";

        //Build the
        if ($this->isMultipleSelect()) {
            $js .=      "if (tmpObject[option.value] != undefined && tmpObject[option.value]) {";
            $js .=          "option.selected = true;";
            $js .=      "}";
        } else {
            $js .=      "if (option.value == templateData.$columnName) {";
            $js .=          "option.selected = true;";
            $js .=          "return;";
            $js .=      "}";
        }

        $js     .=  "});";

        //Return the javascript code
        return $js;
    }
}