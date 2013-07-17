<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_IndexController
    extends Mage_Core_Controller_Front_Action
{
//    /**
//     * Imports the error list from csv to xml.
//     */
//    public function importerrorsAction()
//    {
//        /* @var $error Dotsource_Paymentoperator_Model_Error_Handler_Message */
//        $error = Mage::getModel('paymentoperator/error_handler_message');
//        $csv = new Varien_File_Csv();
//
//        $csv->setLineLength(16384)
//            ->setDelimiter(';');
//
//        $data = $csv->getDataPairs(Mage::getConfig()->getModuleDir(null, 'Dotsource_Paymentoperator').DS.'errorcodes.csv', 0 , 2);
//
//        $xml  = new XMLWriter();
//        $xml->openMemory();
//        $xml->setIndent(true);
//        $xml->setIndentString('    ');
//        $xml->startElement('backend');
//
//        $xmlErrors  = new XMLWriter();
//        $xmlErrors->openMemory();
//        $xmlErrors->setIndent(true);
//        $xmlErrors->setIndentString('    ');
//        $xmlErrors->startElement('backend');
//        $xmlErrors->startElement('error');
//        $xmlErrors->startAttribute('code');
//
//        foreach ($data as $code => $errormsg) {
//            $xmlErrors->text($error->prepareCode($code)." ");
//
//            $xml->startElement('error');
//                $xml->writeAttribute('code', $error->prepareCode($code));
//                $xml->text($errormsg);
//            $xml->endElement();
//        }
//
//        $xmlErrors->endAttribute();
//        $xml->endElement();
//
//        echo '<pre>';
//        echo str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $xml->outputMemory());
//        echo '</br></br></br>';
//        echo str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $xmlErrors->outputMemory());
//        echo '</pre>';
//    }


    /**
     * Return the paymentoperator helper.
     *
     * @return Dotsource_Paymentoperator_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('paymentoperator');
    }
}