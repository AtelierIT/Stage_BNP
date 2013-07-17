<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
    extends Dotsource_Paymentoperator_Controller_Callback
{

    /**
     * Return the payment code to check the response is process
     * by the right controller.
     *
     * @return string
     */
    protected abstract function _getPaymentCode();

    /**
     * @see Dotsource_Paymentoperator_Controller_Callback::notifyAction()
     */
    public function notifyAction()
    {
        try {
            //Parse the response as an response object
            $response   = $this->_getCallbackResponse(
                $this->getRequest()->getParams(),
                true
            );

            //the trans id is the order increment id
            $transId    = $response->getResponse()->getTransid();

            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($transId);

            //Check for the right controller call
            if ($this->_getPaymentCode() != $order->getPayment()->getMethodInstance()->getCode()
                || $this->_getPaymentCode() != $response->getUserData()->getPaymentCode()
            ) {
                Mage::throwException("Wrong controller is called for this payment.");
            }

            //Set the order locale
            Mage::app()->getLocale()->emulate($order->getStoreId());

            //Register the order
            Mage::register('paymentoperator_notify_order', $order);

            //Add message prefix
            $payment = $order->getPayment();
            $payment->setPreparedMessage(Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENTOPERATOR_PREFIX);

            //Log the response
            Mage::getModel('paymentoperator/action')
                ->logResponse(
                    $response,
                    $payment
                )
                ->save();

            //Process the right action
            if (!$response->hasError()) {
                //Add the payment information
                $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                    $payment,
                    $response
                );

                //No need to close the transaction in any case (auth, capture)
                $payment->setIsTransactionClosed(false);

                //Process the success action
                $this->_notifySuccessProcessing($response);
            } else {
                $this->_notifyErrorProcessing($response);
            }

            //Save the changes
            $order->save();

            //Reset to default locale
            Mage::app()->getLocale()->revert();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_notifyActionError();
        }
    }


    /**
     * Return the customer browser back to the cart to the error message.
     */
    public function failureAction()
    {
        //Parse the response as an response object
        $response = $this->_getCallbackResponse(
            $this->getRequest()->getParams(),
            false
        );

        //Reactivate the last used quote
        $this->_getHelper()->restoreQuote(null, Dotsource_Paymentoperator_Helper_Data::FRONTEND);

        $this->_getHelper()->Log($this->_getHelper()->getQuote()->getPayment()->getMethod());

        $this->_getHelper()->getPaymentHelper()
            ->getPaymentErrorManager()
            ->removeHandler('exception')
            ->addHandler('session', 'session', array('session' => 'checkout/session'))
            ->processErrorCode(
                $response->getResponse()->getCode(),
                $this->_getHelper()->getQuote()->getPayment()->getMethod()
            );

        $this->_failureRedirect();
    }


    /**
     * Redirect the customer browser to the success page.
     */
    public function successAction()
    {
        $this->_successRedirect();
    }


    /**
     * Redirect the customer browser back to the onepage checkout.
     */
    public function backAction()
    {
        //Get the checkout session
        $checkout       = $this->_getHelper()->getFrontendCheckoutSession();
        $cancelStatus   = array(
            Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_CAPTURE,
            Dotsource_Paymentoperator_Model_Payment_Abstract::WAITING_AUTHORIZATION
        );

        //Check for a last real order id
        if ($checkout->hasLastRealOrderId()) {
            /* @var $order Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId(
                $checkout->getLastRealOrderId()
            );

            //Check for an valid order
            if ($order instanceof Mage_Sales_Model_Order
                && $order->hasData()
                && $order->hasStatus()
            ) {
                //Check if we can unhold the state and we have an pending payment
                if ($order->canUnhold()
                    && in_array($order->getStatus(), $cancelStatus)
                ) {
                    //Unhold first
                    $order->unhold();

                    if ($order->canCancel()) {
                        //Cancel the order an create a comment to the order history
                        $order->registerCancellation(
                            $this->createMessage(
                                $this->_getHelper()->__(
                                    'The customer quit the payment gateway the order will be canceled.'
                                )
                            )
                        );
                    } else { //Can't cancel the order
                        //Check if we can rehold the order
                        if ($order->canHold()) {
                            $order->hold();
                        }

                        //Add the message
                        $order->addStatusHistoryComment(
                            $this->createMessage(
                                $this->_getHelper()->__(
                                    'The customer quit the payment gateway but the order can\'t cancel.'
                                )
                            )
                        );
                    }

                    //Save the changes
                    $order->save();
                }
            }

            //Reactivate the last used quote
            $this->_getHelper()->restoreQuote(null, Dotsource_Paymentoperator_Helper_Data::FRONTEND);
        }

        $this->_backRedirect();
    }


    /**
     * Process the success of the payment.
     *
     * @param Dotsource_Paymentoperator_Model_Payment_Response_Response $response
     */
    protected function _notifySuccessProcessing(Dotsource_Paymentoperator_Model_Payment_Response_Response $response)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order      = Mage::registry('paymentoperator_notify_order');
        $payment    = $order->getPayment();

        //Call the right transaction method
        switch ($response->getUserData()->getCapture()) {
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                $payment->registerAuthorizationNotification($payment->getBaseAmountAuthorized());
                $order->setStatus(Dotsource_Paymentoperator_Model_Payment_Abstract::READY_FOR_CAPTURE);

                //Send the order email
                try {
                    $order->sendNewOrderEmail()->setIsCustomerNotified(true);
                } catch(Exception $e) {
                    Mage::logException($e);
                }
                break;
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING:
                $msg = $payment->getPreparedMessage();
                $msg .= " ".$this->_getHelper()->__(
                    'The book date is %s.',
                    Zend_Date::now()
                        ->addHour($response->getUserData()->getHours())
                        ->get(Zend_Date::DATETIME_LONG)
                );
                $payment->setPreparedMessage($msg);
            case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                $order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING); //Hack
                $payment->registerCaptureNotification($payment->getBaseAmountAuthorized());

                //Send the order email
                try {
                    $order->sendNewOrderEmail()->setIsCustomerNotified(true);
                } catch(Exception $e) {
                    Mage::logException($e);
                }
                break;
            default:
                Mage::throwException("Can't detect the payment moment.");
        }
    }


    /**l
     * Process the failing of the payment.
     *
     * @param Dotsource_Paymentoperator_Model_Payment_Response_Response $response
     */
    protected function _notifyErrorProcessing(Dotsource_Paymentoperator_Model_Payment_Response_Response $response)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::registry('paymentoperator_notify_order');

        //Unhold first
        if ($order->canUnhold()) {
            $order->unhold();
        }

        //Check if we can cancel the order
        if ($order->canCancel()) {
            $msg = "";

            switch ($response->getUserData()->getCapture()) {
                case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING:
                case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::TIMEDBOOKING:
                    $msg = "The capture was failed the order will be canceled.";
                    break;
                case Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE:
                default:
                    $msg = "The authorization was failed the order will be canceled.";
            }

            //Cancel the order an create a comment to the order history
            $order->registerCancellation(
                $this->createMessage(
                    $this->_getHelper()->__($msg)
                )
            );
        } else {
            //Check if we can rehold the order
            if ($order->canHold()) {
                $order->hold();
            }

            //Add the message
            $order->addStatusHistoryComment(
                $this->createMessage(
                    $this->_getHelper()->__("The authorization/capture was failed but the order can't cancel.")
                )
            );
        }
    }


    /**
     * Parse the response from a callback and return a response model.
     *
     * @param array $responseData
     * @param boolean $forcedEncryption
     * @return Dotsource_Paymentoperator_Model_Payment_Response_Response
     */
    protected function _getCallbackResponse(array $responseData = array(), $forcedEncryption = true)
    {
        if (empty($responseData)) {
            $responseData = $this->getRequest()->getParams();
        }

        //Lower case for access
        $responseData = array_change_key_case($responseData, CASE_LOWER);

        //Check for merchant information
        $merchantId = null;
        if (array_key_exists('plain', $responseData)) {
            $merchantId = $responseData['plain'];
        } else if (array_key_exists('merchantid', $responseData)) {
            $merchantId = $responseData['merchantid'];
        } else if (array_key_exists('mid', $responseData)) {
            $merchantId = $responseData['mid'];
        }

        //Create the response model
        /* @var $response Dotsource_Paymentoperator_Model_Payment_Response_Response */
        $response = Mage::getModel('paymentoperator/payment_response_response')
            ->setEncryptionSettings(
                $this->_getHelper()->getConfiguration()->getPaymentSpecificMerchantFieldByMerchantId(
                    $this->_getPaymentCode(),
                    $merchantId
                )
            )
            ->setResponse($responseData, $forcedEncryption);

        //Log the response
        $this->_getHelper()->Log(
            $response->getResponse(),
            'Notify',
            $response->getDangerousTags()
        );

        return $response;
    }


    /**
     * Return the message with an paymentoperator prefix.
     *
     * @param string $message
     * @return string
     */
    public function createMessage($message)
    {
        return Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENTOPERATOR_PREFIX . " " . $message;
    }
}