<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
class Dotsource_Paymentoperator_Model_Payment_Response_Giropay_Check
    extends Dotsource_Paymentoperator_Model_Payment_Response_Response
{

    /**
     * @see Dotsource_Paymentoperator_Model_Payment_Response_Response::hasError()
     *
     * @return boolean
     */
    public function hasError()
    {
        if ($this->isResponseValid()) {
            //check for a ban list
            $list = $this->getResponse()->getData('accibanlist');

            //Check for items
            return empty($list);
        }

        return true;
    }
}