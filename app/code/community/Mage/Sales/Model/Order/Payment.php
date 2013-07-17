<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
    //Get the current magento version
    $version = Mage::getVersion();
    if (version_compare($version, '1.4.0.0', '>=') && version_compare($version, '1.4.1.0', '<')) {
        require 'Payment-1.4.0.X.php';
    } elseif (version_compare($version, '1.4.1.0', '>=') && version_compare($version, '1.4.2.0', '<')) {
        require 'Payment-1.4.1.X.php';
    } elseif (version_compare($version, '1.4.2.0', '>=') && version_compare($version, '1.5.0.0', '<')) {
        require 'Payment-1.4.2.X.php';
    } elseif (version_compare($version, '1.5.0.0', '>=') && version_compare($version, '1.5.1.0', '<')) {
        require 'Payment-1.5.0.X.php';
    } elseif (version_compare($version, '1.5.1.0', '>=') && version_compare($version, '1.5.2.0', '<')) {
        require 'Payment-1.5.1.X.php';
    /*} elseif (version_compare($version, '1.6.0.0', '>=') && version_compare($version, '1.6.1.0', '<')) {
        require 'Payment-1.6.0.X.php';*/
    } elseif (version_compare($version, '1.6.1.0', '>=') && version_compare($version, '1.7.0.0', '<')) {
        require 'Payment-1.6.1.X.php';
    } elseif (version_compare($version, '1.7.0.0', '>=') && version_compare($version, '1.7.1.0', '<')) {
        require 'Payment-1.7.0.X.php';
    } elseif (version_compare($version, '1.9.0.0', '>=') && version_compare($version, '1.10.0.0', '<')) {
        require 'Payment-1.4.1.X.php';
    } elseif (version_compare($version, '1.10.0.0', '>=') && version_compare($version, '1.10.1.0', '<')) {
        require 'Payment-1.10.0.X.php';
    } elseif (version_compare($version, '1.11.2.0', '>=') && version_compare($version, '1.12.0.0', '<')) {
        require 'Payment-1.10.0.X.php';
    } elseif (version_compare($version, '1.12.0.0', '>=') && version_compare($version, '1.12.1.0', '<')) {
        require 'Payment-1.7.0.X.php';
    } else {
        die('Our current Magento version "'. $version .'" is not supported by the Paymentoperator module.');
    }