<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 16.08.2010 14:52:11
 *
 * Contributors:
 * aherold - initial contents
 */

class Dotsource_Dsrevision_Block_Adminhtml_System_Revision
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    /**
     * Return a string for the Backend Field
     *
     * @param Varien_Data_Form_Element_Abstract $element
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //Get the revision id
        $revision = Mage::getModel('dsrevision/revision')->getRevisionId();

        if ($revision) {
            return $revision;
        }

        return 'No Revision found!';
    }
}