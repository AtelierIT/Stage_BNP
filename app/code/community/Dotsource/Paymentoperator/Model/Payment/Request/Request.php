<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Request_Request
    extends Dotsource_Paymentoperator_Object
{

    /** Holds the const for gettings the request as array */
    const REQUEST_AS_ARRAY              = 1;

    /** Holds the request for getting the request as string */
    const REQUEST_AS_STRING             = 2;

    /** Holds the default model code to instanciate the response model */
    const DEFAULT_RESPONSE_MODEL_CODE   = 'paymentoperator/payment_response_response';


    /** If the value is true the response model try to add country zones */
    protected $_useIpZones              = false;

    /** If the value is true the response model try to add country zones */
    protected $_useCountryZones         = false;

    /** Holds a flag if we use encryption */
    protected $_useEncryption           = true;

    /** Holds if the request should use hmac */
    protected $_useHmac                 = true;

    /** Holds if the request will logs by the connection */
    protected $_logRequest              = true;


    /** Holds the current container for the response */
    protected $_requestObject           = null;

    /** Holds the encryption data object */
    protected $_encryptionDataObject    = null;

    /** Holds the user data object */
    protected $_userDataObject          = null;

    /** Holds the request as string */
    protected $_requestArray            = null;

    /** Holds the response model */
    protected $_responseModel           = null;

    /** Holds the dangerous tags we need to remove from the request */
    protected $_dangerousTags           = array('MerchantID');

    /** Holds the payment */
    protected $_payment                 = null;

    /** Holds the payment method */
    protected $_paymentMethod           = null;

    /** Holds the oracle object */
    protected $_oracle                  = null;

    /** Holds a specific amount */
    protected $_amount                  = null;

    /** Holds the referenced transaction model */
    protected $_referencedTransaction   = null;

    /** Default we use the auto as area */
    protected $_paymentoperatorTransactionArea = Dotsource_Paymentoperator_Helper_Data::AUTO;


    /**
     * This abstract method create the real request data.
     */
    protected abstract function _getRequestData();

    /**
     * This abstract method return the file name.
     */
    public abstract function getRequestFile();


    /**
     * Return the used object for parsing the response.
     *
     * @return string
     */
    public function getResponseModelCode()
    {
        return self::DEFAULT_RESPONSE_MODEL_CODE;
    }


    /**
     * Retrieves the base currency code.
     *
     * @return  string
     */
    protected function _getCurrencyCode()
    {
        //Get the current payment
        $payment = $this->getPayment();

        if ($payment instanceof Mage_Sales_Model_Order_Payment) {
            return $payment->getOrder()->getBaseCurrencyCode();
        } elseif ($payment instanceof Mage_Sales_Model_Quote_Payment) {
            return $payment->getQuote()->getBaseCurrencyCode();
        }

        Mage::throwException('Can\'t process the current payment.');
    }


    /**
     * Return the order or the quote reserved inrement id.
     *
     * @return string
     */
    protected function _getIncrementId()
    {
        //Get the current payment
        $payment = $this->getPayment();

        if ($payment instanceof Mage_Sales_Model_Order_Payment) {
            return $payment->getOrder()->getIncrementId();
        } elseif ($payment instanceof Mage_Sales_Model_Quote_Payment) {
            return $payment->getQuote()->reserveOrderId()->getReservedOrderId();
        }

        Mage::throwException('Can\'t process the current payment.');
    }


    /**
     * Do a pre process of the request.
     */
    protected function _preProcessRequestData()
    {
        $request        = $this->_getRequestObject();
        $encryptData    = $this->_getEncryptionDataObject();

        //Add the merchant id in depends on the payment and the currency
        $request['MerchantID'] = $this->_getHelper()->getConfiguration()->getPaymentSpecificMerchantFieldByCurrency(
            $this->getPaymentMethod()->getCode(),
            $this->_getCurrencyCode(),
            'id'
        );

        //We need the merchant id for callbacks
        $encryptData['plain'] = $request['MerchantID'];

        //Check if we use encryption mode for the request and set the encryption
        //settings in depends of the payment and merchant
        if ($this->useEncryption() && !$encryptData->hasEncryptionSettings()) {
            $encryptData->setEncryptionSettings(
                $this->_getHelper()->getConfiguration()->getPaymentSpecificMerchantFieldByMerchantId(
                    $this->getPaymentMethod()->getCode(),
                    $request['MerchantID']
                )
            );
        }

        //Add ip zones to the result
        if ($this->useIpZones() && $this->getPaymentMethod()->useIpZones()) {
            $encryptData['IPZone'] = $this->getPaymentMethod()->convertedIpZone();
            $encryptData['IPAddr'] = Mage::app()->getRequest()->getClientIp();
        }

        //Add country zone if we have all data
        if ($this->useCountryZones() && $this->getPaymentMethod()->useCountryZones()) {
            $encryptData['Zone'] = $this->getPaymentMethod()->convertedCountryZone();
        }
    }


    /**
     * The post process method is used for set the mac if the
     * and used for the encryption part.
     */
    protected function _postProcessRequestData()
    {
        //Get the request objects
        $dirty              = false;
        $request            = $this->_getRequestObject();
        $encryptData        = $this->_getEncryptionDataObject();
        $userData           = $this->_getUserDataObject();

        //Post process for an request
        Mage::dispatchEvent(
            'paymentoperator_post_process_request_befor_send',
            array(
                'request'       => $request,
                'encrypt_data'  => $encryptData,
                'user_data'     => $userData,
            )
        );

        //Check if we have already a mac
        if ($this->useHmac() && $encryptData->isDataEmpty('MAC')) {
            //set the dirty flag for refresh data field
            $dirty = true;

            //Create the mac
            $mac = $encryptData->getEncryptor()->createHmac(
                $encryptData->getDataOrDefault('PayID', ''),
                $encryptData->getDataOrDefault('TransID', ''),
                $request->getDataOrDefault('MerchantID', ''),
                $encryptData->getDataOrDefault('Amount', ''),
                $encryptData->getDataOrDefault('Currency', '')
            );

            //Set the max to the encrypt data
            $encryptData->setData('MAC', $mac);
        } else {
            //Unset the mac
            $encryptData->unsetData('MAC');
        }

        //Check if we have user data we need to convert to string
        if (!is_string($encryptData->getData('UserData'))) {
            $dirty = true;

            if (!$userData->isEmpty()) {
                $encryptData->setData('UserData', base64_encode($userData->toString()));
            } else {
                $encryptData->unsetData('UserData');
            }
        }

        //Check for refresh the data and len field in the request object
        if ($this->useEncryption()) {
            if ($dirty || !is_string($request->getData('Data'))) {
                //Set the data as encrypted string
                $request
                    ->setData('Len', $encryptData->getStringLength())
                    ->setData('Data', $encryptData->toString());
            }
        } else {
            $request->unsetData('Data');
            $request->addData($encryptData);
        }
    }


    /**
     * Create one request object for the request data.
     *
     * @return Dotsource_Paymentoperator_Object
     */
    protected function _getRequestObject()
    {
        if (is_null($this->_requestObject)) {
            $this->_requestObject = new Dotsource_Paymentoperator_Object();
            $this->_requestObject->setEncryption(false);
        }

        return $this->_requestObject;
    }


    /**
     * Create a encryption object for the data container in the request.
     *
     * @return Dotsource_Paymentoperator_Object
     */
    protected function _getEncryptionDataObject()
    {
        if (is_null($this->_encryptionDataObject)) {
            $this->_encryptionDataObject = new Dotsource_Paymentoperator_Object();
            $this->_encryptionDataObject->setEncryption(true);

            //Set the encryption data object to the main request object
            $this->_getRequestObject()->setData('Data', $this->_encryptionDataObject);
        }

        return $this->_encryptionDataObject;
    }


    /**
     * Retrieves the encrypted PayID of the request.
     *
     * @return  string
     */
    public function getPayid()
    {
        return $this->_getEncryptionDataObject()->getData('PayID');
    }


    /**
     * Return the user data container from the request.
     *
     * @return Dotsource_Paymentoperator_Object
     */
    protected function _getUserDataObject()
    {
        if (is_null($this->_userDataObject)) {
            $this->_userDataObject = new Dotsource_Paymentoperator_Object();
            $this->_userDataObject->setEncryption(false);

            //Set the encryption data object to the main request object
            $this->_getEncryptionDataObject()->setData('UserData', $this->_userDataObject);
        }

        return $this->_userDataObject;
    }


    /**
     * Check if we have already a request.
     *
     * @return boolean
     */
    protected function _hasRequest()
    {
        return !is_null($this->_requestArray);
    }


    /**
     * Add the given string or the given array to the
     * dangerous tag list.
     *
     * @param array || string $tag
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    protected function _addDangerousTag($tag)
    {
        //We need an array
        if (!is_array($tag)) {
            $tag = array($tag);
        }

        //Add the given tags to the object tags list
        $this->_dangerousTags = array_merge($this->_dangerousTags, $tag);

        return $this;
    }


    /**
     * Return the array with dangerous tags who need to
     * strip from the xml structure.
     *
     * @return array
     */
    public function getDangerousTag()
    {
        return $this->_dangerousTags;
    }


    /**
     * Return the request as string. The first call of
     * this method return a full request with all tags, all
     * next calls will get the clean request with filtered tags.
     *
     * @return array
     */
    public function getRequest($format = self::REQUEST_AS_ARRAY)
    {
        //Check if we have an response already
        if (!$this->_hasRequest()) {
            //Do the pre process
            $this->_preProcessRequestData();

            //this method writes the data in the xml writer
            $this->_getRequestData();

            //Do the post process
            $this->_postProcessRequestData();

            //Log
            if ($this->_getHelper()->isDemoMode()) {
                if ($this->useEncryption()) {
                    $this->_getHelper()->Log(
                        $this->_getEncryptionDataObject(),
                        "Request ({$this->getRequestFile()})",
                        $this->getDangerousTag()
                    );
                } else {
                    $this->_getHelper()->Log(
                        $this->_getRequestObject(),
                        "Request ({$this->getRequestFile()})",
                        $this->getDangerousTag()
                    );
                }
            }

            //Check for setting up the paymentoperator transaction id
            $this->_syncPaymentoperatorTransactionIdToPayment();

            //Convert the full request to the output format
            $requestData = $this->_formatRequest($this->_getRequestObject(), $format);

            //Now clean the data and request object
            $this->_getConverter()->stripDangerousTags(
                $this->_getRequestObject(),
                $this->getDangerousTag()
            );

            //Convert the filtered data in the output format
            $this->_requestArray = $this->_formatRequest($this->_getRequestObject(), $format);

            //The first call get the full request all other get the clean request array
            return $requestData;
        }

        //Return the response
        return $this->_requestArray;
    }


    /**
     * Set the paymentoperator transaction id if the current payment has no
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    protected function _syncPaymentoperatorTransactionIdToPayment()
    {
        //We need a payment for that
        if (!$this->hasPayment()) {
            return $this;
        }

        //Check if we have already a paymentoperator transaction id
        if (!$this->getPayment()->getPaymentoperatorTransactionId()) {
            //Get the transaction model
            $transactionModel = $this->getPaymentoperatorTransactionModel();

            //If the model has an id we store this in the payment
            if ($transactionModel->hasId()) {
                $this->getPayment()->setPaymentoperatorTransactionId($transactionModel->getId());
            }
        }

        return $this;
    }


    /**
     * Formats the given request and the given data in the given format.
     *
     * @param mixed $request
     * @param int $format
     * @return mixed
     */
    protected function _formatRequest($request, $format)
    {
        if (self::REQUEST_AS_ARRAY === $format) {
            return $request->toArray();
        } elseif (self::REQUEST_AS_STRING === $format) {
            return $request->toString();
        }

        Mage::throwException("Can't process the given format.");
    }


    /**
     * Create a response model from the given response string.
     *
     * @param string $responseString
     * @param boolean $forceEncryption
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function setResponse($responseString, $forceEncryption = true)
    {
        //This method can only used once per object
        if (null === $this->_responseModel) {
            //Create the response object
            $this->_responseModel = Mage::getModel($this->getResponseModelCode());

            //check for the right object type
            if ($this->_responseModel instanceof Dotsource_Paymentoperator_Model_Payment_Response_Response) {
                //Set the encryptor model for decryption to the response model
                if ($this->useEncryption()) {
                    $this->_responseModel->setEncryptor($this->_getEncryptionDataObject()->getEncryptor());
                }

                //Set the response
                $this->_responseModel->setResponse($responseString, $forceEncryption);
                $this->_responseModel->setRequest($this);
            } else {
                throw new Exception(
                    'The object must be an instance of Dotsource_Paymentoperator_Model_Payment_Response_Response.'
                );
            }
        } else {
            throw new Exception('The request object has already a response.');
        }

        return $this;
    }


    /**
     * @see Dotsource_Paymentoperator_Object::toString()
     *
     * @param string $noNeed
     * @return string
     */
    public function toString($noNeed = null)
    {
        //If we have an request object we return the string from the request object
        if ($this->_hasRequest()) {
            return $this->_getRequestObject()->toString();
        }

        return "";
    }


    /**
     * Return the response model who was created
     * with the method setResponse(...).
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Response_Response
     */
    public function getResponseModel()
    {
        return $this->_responseModel;
    }


    /**
     * Set the payment for the request action.
     * Allowed are orders, quotes and payments.
     *
     * @param mixed $payment
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function setPayment($payment)
    {
        if ($payment instanceof Mage_Payment_Model_Info) { //Payment instance
            $this->_payment = $payment;
        } elseif ($payment instanceof Mage_Payment_Model_Method_Abstract) { //Payment method
            $this->_payment = $payment->getInfoInstance();
        } elseif ($payment instanceof Mage_Sales_Model_Order //Order, Quote
            || $payment instanceof Mage_Sales_Model_Quote
        ) {
            $this->_payment = $payment->getPayment();
        }

        return $this;
    }


    /**
     * Return the payment.
     *
     * @return Mage_Sales_Model_Order_Payment
     */
    public function getPayment()
    {
        return $this->_payment;
    }


    /**
     * Checks if an payment is set.
     *
     * @return boolean
     */
    public function hasPayment()
    {
        return null !== $this->getPayment();
    }


    /**
     * Return the payment method.
     *
     * @return Dotsource_Paymentoperator_Model_Payment_Abstract
     */
    public function getPaymentMethod()
    {
        return $this->_paymentMethod;
    }


    /**
     * Set the payment method.
     *
     * @param Mage_Payment_Model_Method_Abstract $paymentMethod
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function setPaymentMethod(Mage_Payment_Model_Method_Abstract $paymentMethod)
    {
        $this->_paymentMethod = $paymentMethod;
        return $this;
    }


    /**
     * Return true if we have an payment method.
     *
     * @return boolean
     */
    public function hasPaymentMethod()
    {
        return null !== $this->getPaymentMethod();
    }


    /**
     * Checks if the request model has an specific amount.
     *
     * @return boolean
     */
    public function hasAmount()
    {
        return null !== $this->getAmount();
    }


    /**
     * Return the specific amount.
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->_amount;
    }


    /**
     * Set a specific amount for the request.
     *
     * @param float $amount
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function setAmount($amount)
    {
        $this->_amount = $amount;
        return $this;
    }


    /**
     * Set the paymentoperator transaction area.
     *
     * @param int $_paymentoperatorTransactionArea
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function setPaymentoperatorTransactionArea($paymentoperatorTransactionArea)
    {
        $this->_paymentoperatorTransactionArea = $paymentoperatorTransactionArea;
        return $this;
    }


    /**
     * Return the paymentoperator area.
     *
     * @return int
     */
    public function getPaymentoperatorTransactionArea()
    {
        return $this->_paymentoperatorTransactionArea;
    }


    /**
     * Check if the request has an referenced transaction model.
     *
     * @return boolean
     */
    public function hasReferencedTransactionModel()
    {
        return $this->getReferencedTransactionModel() instanceof Mage_Sales_Model_Order_Payment_Transaction;
    }


    /**
     * Return the referenced transaction model.
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function getReferencedTransactionModel()
    {
        return $this->_referencedTransaction;
    }


    /**
     * Set a referenced transaction model.
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $referencedTransactionModel
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    public function setReferencedTransactionModel(
        Mage_Sales_Model_Order_Payment_Transaction $referencedTransactionModel
    )
    {
        $this->_referencedTransaction = $referencedTransactionModel;
        return $this;
    }


    /**
     * Return the paymentoperator transaction model in depends of the configured area.
     *
     * @return Dotsource_Paymentoperator_Model_Transaction
     */
    public function getPaymentoperatorTransactionModel()
    {
        $transactionId = null;

        //Get the previous transaction id from the current payment
        if ($this->hasPayment() && $this->getPayment()->getPaymentoperatorTransactionId()) {
            $transactionId = $this->getPayment()->getPaymentoperatorTransactionId();
        }

        //Return the transaction model
        $transactionModel = $this->_getHelper()->getTransactionModel(
            $transactionId,
            $this->getPaymentoperatorTransactionArea()
        );

        return $transactionModel;
    }


    /**
     * Return if we can use ip zones.
     *
     * @return boolean
     */
    public function useIpZones()
    {
        return $this->_useIpZones;
    }


    /**
     * Return if we can use country zones.
     *
     * @return unknown
     */
    public function useCountryZones()
    {
        return $this->_useCountryZones;
    }


    /**
     * First the method try to return the billing address object from the
     * object data container. If no billing address object is available
     * the billing address from the quote is getting returned.
     *
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function _getBillingAddress()
    {
        //Check for an billing object in the current data container
        if ($this->hasBillingAddress()) {
            return $this->getBillingAddress();
        }

        //Check if we can use the billing address from the order
        if ($this->hasPayment()) {
            $payment = $this->getPayment();

            if ($payment instanceof Mage_Sales_Model_Order_Payment) {
                return $payment->getOrder()->getBillingAddress();
            } elseif ($payment instanceof Mage_Sales_Model_Quote_Payment) {
                return $payment->getQuote()->getBillingAddress();
            }
        }

        //Get the billing address from the current quote
        return $this->_getHelper()->getQuote()->getBillingAddress();
    }


    /**
     * Return the email address from the customer.
     *
     * @return string
     */
    protected function _getEmailAddress()
    {
        //Check if the current customer has an email address
        $customer = $this->_getHelper()->getCustomer();

        if (!empty($customer) && $customer->hasEmail()) {
            return $customer->getEmail();
        }

        //Check if we can get the email from the quote or order
        $model = null;
        if ($this->hasPayment()) {
            $model = $this->getPayment()->getOrder();
        } else {
            $model = $this->_getHelper()->getQuote();
        }

        //Check for quote or the order from the email address
        if ($model->hasCustomerEmail()) {
            return $model->getCustomerEmail();
        } else if ($this->getBillingAddress()->hasEmail()) {
            return $this->getBillingAddress()->getEmail();
        }

        Mage::throwException("Can't find a email address.");
    }


    /**
     * Return the oracle object.
     *
     * @return Dotsource_Paymentoperator_Model_Oracle_Type_Order
     */
    public function getOracle()
    {
        if (null === $this->_oracle) {
            $this->_oracle = Mage::getModel('paymentoperator/oracle_type_order')->setModel(
                $this->getPayment()
            );
        }

        return $this->_oracle;
    }


    /**
     * Return is we use encryption.
     *
     * @return boolean
     */
    public function useEncryption()
    {
        return $this->_useEncryption;
    }


    /**
     * Return if the request should use hmac.
     *
     * @return boolean
     */
    public function useHmac()
    {
        return $this->_useHmac;
    }


    /**
     * Return if the current request should be logged
     *
     * @return boolean
     */
    public function logRequest()
    {
        return $this->_logRequest;
    }


    /**
     * Return the paymentoperator converter.
     *
     * @return Dotsource_Paymentoperator_Helper_Converter
     */
    protected function _getConverter()
    {
        return Mage::helper('paymentoperator/converter');
    }


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