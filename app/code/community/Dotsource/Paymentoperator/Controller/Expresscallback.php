<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Created:
 * 18.05.2010 17:00:52
 *
 * Contributors:
 * dcarl - initial contents
 */

abstract class Dotsource_Paymentoperator_Controller_Expresscallback
    extends Dotsource_Paymentoperator_Controller_Paymentoperatorcallback
{

    /**
     * @see Dotsource_Paymentoperator_Controller_Callback::notifyAction()
     *
     * @return  null
     */
    public function notifyAction()
    {
        try {
            //Parse the response as an response object
            $response = $this->_getCallbackResponse(
                $this->getRequest()->getParams(),
                false
            );

            //the trans id is the order entity id
            $transId = $response->getResponse()->getTransid();

            //Load the order or the quote model
            $model = $this->_loadOrderOrQuote($transId);

            //No need to process
            if (null === $model) {
                return;
            }

            //Get the payment from the order or quote
            $payment = $model->getPayment();

            //Check for the right controller call
            if ($this->_getPaymentCode() != $payment->getMethodInstance()->getCode()
                || $this->_getPaymentCode() != $payment->getMethodInstance()->getCode()
            ) {
                return;
            }

            //Add message prefix
            $payment->setPreparedMessage(
                Dotsource_Paymentoperator_Model_Payment_Abstract::PAYMENTOPERATOR_PREFIX
            );

            //Log the response
            Mage::getModel('paymentoperator/action')->logResponse($response, $payment)->save();

            //Process the right action
            if (!$response->hasError()) {
                //Add the payment information
                $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                    $payment,
                    $response
                );

                //Process the quote
                if ($model instanceof Mage_Sales_Model_Quote) {
                    //Log the essential response data in the payment
                    $payment->setAdditionalInformation(
                        'paymentoperator_xid', $response->getResponse()->getXid()
                    );

                    $payment->setAdditionalInformation(
                        'paymentoperator_payid', $response->getResponse()->getPayid()
                    );

                    //Save the capture in the payment
                    $payment->setAdditionalInformation(
                        'paymentoperator_capture', $response->getUserData()->getCapture()
                    );

                    //Save status to the payment
                    $payment->setAdditionalInformation(
                        'paymentoperator_is_pending', $response->isPending()
                    );

                    //Remove all addresses
                    $model->removeAllAddresses();

                    //Add the addresses from paypal to the quote
                    $model->setShippingAddress(
                        $this->_prepareQuoteShippingAddress($response->getResponse())
                    );

                    $model->setBillingAddress(
                        $this->_prepareQuoteBillingAddress($response->getResponse())
                    );

                    $model->collectTotals()->save();
                } else if ($model instanceof Mage_Sales_Model_Order) {
                    $this->_notifySuccessProcessing($response);
                    $model->save();
                }
            } else if ($model instanceof Mage_Sales_Model_Order) {
                //Cancel order if one exists
                $this->_notifyErrorProcessing($response);
                $model->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_notifyActionError();
        }
    }


    /**
     * @see Dotsource_Paymentoperator_Controller_Callback::successAction()
     */
    public function successAction()
    {
        //Parse the response as an response object
        $response = $this->_getCallbackResponse(
            $this->getRequest()->getParams(),
            true
        );

        //TransID is the quote reserved increment id
        $transId = $response->getResponse()->getTransid();

        //Load the order or the quote model
        $model = $this->_loadOrderOrQuote($transId);

        //If the order currently not exists we are currently in the checkout step
        //so if the success action is called we can redirect to the review step
        if (null === $model) {
            $this->_failureRedirect();
            return;
        } else if ($model instanceof Mage_Sales_Model_Quote) {
            //Valid the quote
            if (!$this->_validateQuote($model)) {
                return;
            }

            //Force the quote for the review step to the quote from the request
            $this->_getHelper()->getFrontendCheckoutSession()->setQuoteId($model->getId());

            //Redirect to the review page
            $this->_redirect('*/*/review');
            return;
        }

        $this->_successRedirect();
    }


    /**
     * Review action that generates the overview of the
     * shipping and billing addresses.
     *
     * @return  null
     */
    public function reviewAction()
    {
        try {
            //Get the current frontend quote
            $quote = $this->_getHelper()->getFrontendQuote();

            //Redirect to the cart
            if (!$this->_validateQuote($quote)) {
                return;
            }

            $this->loadLayout();
            $this->_initLayoutMessages('checkout/session');
            $this->getLayout()
                ->getBlock('paymentoperator.checkout.paypal.review')
                ->setQuote($quote)
                ->setCanEditShippingAddress(false)
                ->getChild('details')->setQuote($quote);
            $this->renderLayout();
            return;
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('checkout/session')->addError(
                $this->__('Unable to initialize Express Checkout review.')
            );
        }

        //Back to the cart
        $this->_failureRedirect();
    }


    /**
     * Submit the order.
     *
     * @return  null
     */
    public function placeOrderAction()
    {
        try {
            //Get the session
            $session = $this->_getHelper()->getFrontendCheckoutSession();

            //Get the current frontend quote
            $quote = $session->getQuote();

            //Check for the right payment type and throw an exception
            if (!$this->_validateQuote($quote)) {
                return;
            }

            if ($this->getRequest()->getParam('shipping_method')) {
                $this->_updateShippingMethod(
                    $this->getRequest()->getParam('shipping_method')
                );
            }

            if (!$quote->getCustomerId()) {
                $quote
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)
                    ->setCustomerEmail($quote->getBillingAddress()->getEmail());
            }

            //Ignore address validation errors
            $quote->getBillingAddress()->setShouldIgnoreValidation(true);
            if (!$quote->getIsVirtual()) {
                $quote->getShippingAddress()->setShouldIgnoreValidation(true);
            }

            //Recalculate the quote
            $quote->collectTotals();

            //Get the quote to order service model
            /* @var $order Mage_Sales_Model_Order */
            $order = null;
            $callbackProcessed = false;
            $service = Mage::getModel('sales/service_quote', $quote);
            $callbackMethods = array('submitOrder', 'submit');

            //Process one the given callbacks
            foreach ($callbackMethods as $method) {
                if (method_exists($service, $method)) {
                    $order = call_user_func(array($service, $method));
                    $callbackProcessed = true;
                    break;
                }
            }

            //Quoted submit failed
            if ($callbackProcessed === false) {
                throw new Excpetion('Quote submit failed. Callback method not found.');
            }

            //Check for getOrder method
            if (method_exists($service, 'getOrder')) {
                $order = $service->getOrder();
            }

            //TODO: Statecheck
//            switch ($order->getState()) {
//                case Mage_Sales_Model_Order::STATE_PENDING_PAYMENT:
//                    break;
//                case Mage_Sales_Model_Order::STATE_PROCESSING:
//                case Mage_Sales_Model_Order::STATE_COMPLETE:
                    try {
                        $order->sendNewOrderEmail();
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
//                    break;
//            }

            //Save the quote to save is_active 0
            $quote->save();

            //Reset the session information
            if (method_exists($session, 'clearHelperData')) {
               $session->clearHelperData();
            }

            //Set the helper data
            $session
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId());

            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getHelper()->getFrontendCheckoutSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getHelper()->getFrontendCheckoutSession()->addError($this->__('Unable to place the order.'));
        }

        //Back to the cart
        $this->_failureRedirect();
    }


    /**
     * Set shipping method to quote, if needed.
     *
     * @param   string  $methodCode
     */
    protected function _updateShippingMethod($methodCode)
    {
        $quote = $this->_getHelper()->getFrontendQuote();

        if (!$quote->getIsVirtual() && $shippingAddress = $quote->getShippingAddress()) {
            if ($methodCode != $shippingAddress->getShippingMethod()) {
                $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
                $quote->collectTotals()->save();
            }
        }
    }


    /**
     * Update shipping method (combined action for ajax and regular request).
     *
     * @return  null
     */
    public function saveShippingMethodAction()
    {
        try {
            $this->_updateShippingMethod($this->getRequest()->getParam('shipping_method'));
            $isAjax = $this->getRequest()->getParam('isAjax');
            if ($isAjax) {
                $this->loadLayout('paymentoperator_callback_paypalexpress_details');
                $this->getResponse()->setBody($this->getLayout()->getBlock('root')
                    ->setQuote($this->_getHelper()->getFrontendQuote())
                    ->toHtml());
                return;
            }
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('Unable to update shipping method.'));
        }
        if ($isAjax) {
            $this->getResponse()->setBody('<script type="text/javascript">window.location.href = '
                . Mage::getUrl('*/*/review') . ';</script>');
        } else {
            $this->_redirect('*/*/review', array('_current' => true));
        }
    }


    /**
     * Set the response data to the shipping and billing addresses of
     * the given quote.
     *
     * @param   Dotsource_Paymentoperator_Object   $response
     * @return  Mage_Sales_Model_Quote_Address
     */
    protected function _prepareQuoteShippingAddress(Dotsource_Paymentoperator_Object $response)
    {
        //Prepare the shipping address
        /* @var $address Mage_Sales_Model_Quote_Address */
        $address = Mage::getModel('sales/quote_address');
        $address->setStreet(
            array(
                $response->getData('addrstreet'),
                $response->getData('addrstreet2')
            )
        );

        $address
            ->setCity($response->getData('addrcity'))
            ->setRegion($response->getData('addrstate'))
            ->setPostcode($response->getData('addrzip'))
            ->setCountryId($response->getData('addrcountrycode'))
            ->setTelephone($response->getData('phone'));

        //Set the name to the address
        Mage::helper('paymentoperator/converter')->setNameToAddress(
            $address, $response->getData('name')
        );

        $address->implodeStreetAddress();
        $address->setCollectShippingRates(true);

        return $address;
    }


    /**
     * Set the response data as billing address of the given quote.
     *
     * @param   Dotsource_Paymentoperator_Object   $response
     * @return  Mage_Sales_Model_Quote_Address
     */
    protected function _prepareQuoteBillingAddress(Dotsource_Paymentoperator_Object $response)
    {
        /* @var $address Mage_Sales_Model_Quote_Address */
        //If not sent, use the shipping Address
        if (!$response->getData('billingname')) {
            $address = $this->_prepareQuoteShippingAddress($response);
        } else {
            $address = Mage::getModel('sales/quote_address');
            $address->setStreet(
                array(
                    $response->getData('billingaddrstreet'),
                    $response->getData('billingaddrstreet2')
                )
            );
            $address->setCity($response->getData('billingaddrcity'))
                ->setRegion($response->getData('billingaddrstate'))
                ->setPostcode($response->getData('billingaddrzip'))
                ->setCountryId($response->getData('billingaddrcountrycode'));

            //Set the name to the address
            Mage::helper('paymentoperator/converter')->setNameToAddress(
                $address, $response->getData('billingname')
            );

            $address->implodeStreetAddress();
        }
        $address->setEmail($response->getData('e-mail'));

        return $address;
    }


    /**
     * Return the order from the given increment id.
     *
     * @param string $incrementId
     * @return Mage_Sales_Model_Order || null
     */
    protected function _loadOrder($incrementId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);

        if ($order && $order->getId()) {
            return $order;
        }

        return null;
    }


    /**
     * Return the quote from the given reserved increment id.
     *
     * @param string $reservedIncrementId
     * @return Mage_Sales_Model_Quote || null
     */
    protected function _loadQuote($reservedIncrementId)
    {
        $quote = Mage::getModel('sales/quote')->load($reservedIncrementId, 'reserved_order_id');

        if ($quote && $quote->getId()) {
            return $quote;
        }

        return null;
    }


    /**
     * Try to return a order with the given increment id. If the order don't exists
     * the method try to return a quote with the given increment id (reserved_order_id).
     * If the quote not exists the method return null.
     * If the method found an order is will saved under the mage register key paymentoperator_notify_order
     * or a quote will saved under the key paymentoperator_notify_quote.
     *
     * @param string $incrementId
     * @return unknown
     */
    protected function _loadOrderOrQuote($incrementId)
    {
        //First try to load the order
        $order = $this->_loadOrder($incrementId);

        if ($order instanceof Mage_Sales_Model_Order) {
            Mage::register('paymentoperator_notify_order', $order);
            return $order;
        }

        //Try to load an quote
        $quote = $this->_loadQuote($incrementId);

        if ($quote instanceof Mage_Sales_Model_Quote) {
            Mage::register('paymentoperator_notify_quote', $quote);
            return $quote;
        }

        return null;
    }


    /**
     * A valid quote has use the right controller, has items and has an
     * valid paymentoperator transaction id.
     *
     * @param Mage_Sales_Model_Quote || Mage_Sales_Model_Order $model
     * @return boolean
     */
    protected function _validateQuote($model, $errorHandling = true)
    {
        $validQuote = $this->_getPaymentCode() == $model->getPayment()->getMethodInstance()->getCode()
            && $model->hasItems()
            && (boolean)$model->getPayment()->getData('paymentoperator_transaction_id');

        if (!$validQuote && $errorHandling) {
            $this->_getHelper()->getFrontendCheckoutSession()->addError(
                $this->_getHelper()->__("You'r current quote is not valid to use PayPal express checkout.")
            );

            //Redirect to cart
            $this->_redirect('checkout/cart/');
        }

        return $validQuote;
    }
}