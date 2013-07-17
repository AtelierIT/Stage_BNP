<?php
/**
 * Copyright (c) 2008-2011 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
interface Dotsource_Paymentoperator_Model_Check_Risk_Storage_Backend_Interface
{

    /**
     * Transforms the data on setData method.
     *
     * @param mixed $data
     * @return mixed
     */
    public function getData($data);

    /**
     * Transforms the data on getData method.
     *
     * @param mixed $data
     * @return mixed
     */
    public function setData($data);
}