<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Block_Info_Billpay_Ratepay_Rateoverview
    extends Mage_Core_Block_Template
{

    /**
     * @see Mage_Core_Block_Template::_construct()
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paymentoperator/info/billpay/ratepay/rateoverview.phtml');
    }

    /**
     * Return the pdf version of the rate plan overview.
     *
     * @return  string
     */
    public function toPdf()
    {
        $this->setTemplate('paymentoperator/info/pdf/billpay/ratepay/rateoverview.phtml');
        return $this->toHtml();
    }

    /**
     * Return the order.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_getData('order');
    }

    /**
     * Return the payment plan.
     *
     * @return  array
     */
    public function getPaymentPlan()
    {
        return $this->_getData('payment_plan');
    }

    /**
     * Return the conditions.
     *
     * @return  array
     */
    public function getConditions()
    {
        return $this->_getData('conditions');
    }

    /**
     * @http://www.ingen-networks.com/2009/10/php-ordinal-numbers-3rd-4th-1st-etc/
     *
     * @param   string  $number
     * @return  string
     */
    public function formatRateNumber($number)
    {
        if (($number % 100) > 10 && ($number % 100) < 14) {
            $suffix = "th";
        } else {
            switch($number % 10) {
                case 0:
                    $suffix = "th";
                    break;
                case 1:
                    $suffix = "st";
                    break;
                case 2:
                    $suffix = "nd";
                    break;
                case 3:
                    $suffix = "rd";
                    break;
                default:
                    $suffix = "th";
                    break;
            }
        }

        return "{$number}{$suffix}";
    }

    /**
     * Return a formated price.
     *
     * @param   string  $price
     * @return  string
     */
    public function formatRatePrice($price)
    {
        return $this->getOrder()->formatBasePrice($price);
    }

    /**
     * Return a formated date. If the given date is empty the default value
     * will return.
     *
     * @param   string  $date
     * @param   string  $default
     * @return  string
     */
    public function formatRateDate($date, $default = "-")
    {
        if (!$date) {
            return $default;
        }

        return $date;
    }

    /**
     * Return the whitespace formatted column for pdf visualization.
     *
     * @param   string  $number
     * @param   string  $amount
     * @param   string  $date
     * @return  string
     */
    public function getPdfColumn($number, $amount, $date)
    {
        return sprintf(
            "%s %s %s",
            $number,
            $amount,
            $date
        );
    }
}