<?php
/**
 * Copyright (c) 2008-2010 dotSource GmbH.
 * All rights reserved.
 * http://www.dotsource.de
 *
 * Contributors:
 * Erik Wohllebe - initial contents
 */
abstract class Dotsource_Paymentoperator_Model_Payment_Abstract
    extends Mage_Payment_Model_Method_Abstract
{

    /**
     * Holds the key where the data will be backuped.
     *
     * @var string
     */
    const PAYMENT_INFORMATION_BACKUP_KEY    = '__payment_information_backup';

    /** Holds the transaction prefix */
    const PAYMENTOPERATOR_PREFIX                   = 'PaymentOperator Payment:';

    const WAITING_AUTHORIZATION             = 'waiting_auth_poperator_note';

    const WAITING_CAPTURE                   = 'waiting_capture_poperator_note';

    const READY_FOR_CAPTURE                 = 'ready_poperator_capture';


    protected $_isGateway                   = true;

    protected $_canAuthorize                = true;

    protected $_canCapture                  = true;

    protected $_canCapturePartial           = true;

    protected $_canRefund                   = true;

    protected $_canRefundInvoicePartial     = true;

    protected $_canVoid                     = true;

    /** Don't available in backend */
    protected $_canUseInternal              = false;

    protected $_canUseCheckout              = true;

    protected $_canUseForMultishipping      = true;

    protected $_canManageRecurringProfiles  = false;

    /** Check if we can capture direct from the backend */
    protected $_canBackendDirectCapture     = false;

    /**
     * If this is false a capture can only void on the same date
     * @var boolean
     */
    protected $_ignoreDateByVoidCapture     = false;

    /** Holds the path to the model */
    protected $_requestModelInfo            = null;

    /** Holds the default path for the request models */
    protected $_requestModelDefault         = 'paymentoperator/payment_request_default_';

    /**
     * Holds the process cancel model
     * @var Dotsource_Paymentoperator_Model_Cancelprocess|null
     */
    protected $_cancelProcessModel          = null;

    /** Holds a oracle model*/
    protected $_oracle                      = null;

    /** Holds the payment information fields */
    protected $_paymentInformationFields    = array();

    /**
     * Holds the payment information model.
     *
     * @var Dotsource_Paymentoperator_Model_Paymentinformation
     */
    protected $_paymentInformationModel     = null;

    /** Holds the redirect url */
    //Static to fix issue
    protected static $_redirectUrl          = null;


    /**
     * @see Mage_Payment_Model_Method_Abstract::canUseCheckout()
     *
     * @return boolean
     */
    public function canUseCheckout()
    {
        //The payment method can use if the payment method can use in checkout and demo mode is active or
        //the payment method is not currently blocked
        return parent::canUseCheckout()
            && $this->_getHelper()->isGlobalActive(true)
            && ($this->_getHelper()->isDemoMode() || !$this->_getHelper()->getPaymentHelper()->isPaymentDisabled($this->getCode()));
    }

    /**
     * Disable payment method if it's global deactivated.
     *
     * @return boolean
     */
    public function canUseInternal()
    {
        return parent::canUseInternal() && $this->_getHelper()->isGlobalActive(true);
    }

    /**
     * Create an return a configured request model.
     *
     * @param string $requestModelName
     * @param Varien_Object $payment
     * @return Dotsource_Paymentoperator_Model_Payment_Request_Request
     */
    protected function _createRequestModel($requestModelName, Varien_Object $payment, $paymentoperatorArea = null)
    {
        //Set auto as default
        if (null === $paymentoperatorArea) {
            $paymentoperatorArea = Dotsource_Paymentoperator_Helper_Data::AUTO;
        }

        //We only can ship invoiced items
        $payment->setPreparedMessage(self::PAYMENTOPERATOR_PREFIX);

        //Get the right request model and set the payment model
        $request = null;

        //Check if the given request model is a full model path
        $isFullModelPath = strpos($requestModelName, '/');

        try {
            if ($isFullModelPath) {
                $request = Mage::getModel($requestModelName);
            } else {
                $request = Mage::getModel($this->_getRequestModelPath().$requestModelName);
            }
        } catch (Exception $e) {
        }

        //Check for default request
        if (empty($request) && !$isFullModelPath) {
            $request = Mage::getModel($this->_getDefaultRequestModelPath().$requestModelName);
        }

        //Check if we have a request model
        if (!$request) {
            throw new Exception("Request model \"{$requestModelName}\" could not found.");
        }

        //Register the current payment object
        $request
            ->setPaymentoperatorTransactionArea($paymentoperatorArea)
            ->setPayment($payment)
            ->setPaymentMethod($this);

        //Return the configured request model
        return $request;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::authorize()
     *
     * @param Varien_Object $payment
     * @param unknown_type $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        //Get the request model
        $request = $this->_createRequestModel('authorize', $payment);

        //No need to close the transaction in any case (auth, capture)
        $payment->setIsTransactionClosed(false);

        //Set the amount
        $request->setAmount($amount);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );

            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_authorize_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('authorize')
                ->processErrorCode($request, $this->getCode());
        }

        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::capture()
     *
     * @param Varien_Object $payment
     * @param float $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        //Choose the right backend capture method
        if ($this->_canCaptureAuthorization($payment)) {
            $this->_capture($payment, $amount);
        } elseif ($this->canBackendDirectCapture()) {
            $this->_directBackendCapture($payment, $amount);
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('capture')
                ->processMessage('There is no open authorization to capture.');
        }

        return $this;
    }

    /**
     * Capture an open authorization.
     *
     * @param Varien_Object $payment
     * @param float $amount
     */
    protected function _capture(Varien_Object $payment, $amount)
    {
        //check for open authorization
        $hasAvilableAuth    = $this->_canCaptureAuthorization($payment);

        //We need for etm and non etm a open authorization
        if (!$hasAvilableAuth) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('capture')
                ->processMessage('There is no open authorization to capture.');
        }

        //Get the request model
        $request = $this->_createRequestModel('capture', $payment);

        //Set the authorization transaction model and the invoice amount
        $request
            ->setReferencedTransactionModel($payment->getAuthorizationTransaction())
            ->setAmount($amount);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );

            //Magento checks if the parent transaction is need to close but if etm
            //is disabled we force to close the parent transaction
            if (!$this->getConfigData('etm_active')) {
                $payment->setShouldCloseParentTransaction(true);
            }

            //Don't close the capture transaction we are available to refund
            $payment->setIsTransactionClosed(false);

            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_capture_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('capture')
                ->processErrorCode($request, $this->getCode());
        }
    }

    /**
     * Process a capture if no previous authorization is open but
     * all payment information are available to process a new capture.
     *
     * @param Varien_Object $payment
     * @param float $amount
     */
    protected function _directBackendCapture(Varien_Object $payment, $amount)
    {
        //Get the request model
        $request = $this->_createRequestModel('capture_new', $payment);

        //Set the invoice amount
        $request->setAmount($amount);

        //We do a direct capture
        //Don't close the parant -> we don't have a parent (auth) transaction
        $payment
            ->setParentTransactionId(null)
            ->setShouldCloseParentTransaction(false);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );

            //Don't close the capture transaction we are available to refund
            $payment->setIsTransactionClosed(false);
            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_capture_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('capture')
                ->processErrorCode($request, $this->getCode());
        }
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::cancel()
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        //Do void the amount if we can
        if ($this->canVoid($payment)) {
            $this->void($payment);
            return $this;
        }

        /* @var $order Mage_Sales_Model_Order */
        $order = $payment->getOrder();

        /* @var $cancelProcess Dotsource_Paymentoperator_Model_Cancelprocess */
        $cancelProcess  = $this->getCancelProcessModel($payment);
        $errors         = array();
        $success        = array();

        //Create a special error manager for refund method
        if (null === Mage::registry('error_refund_manager')) {
            $errorRefundManager = $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager()
                ->removeHandler('session')
                ->updateHandler('exception', 'unsetData', 'module');

            //Register the error manager
            Mage::register('error_refund_manager', $errorRefundManager);
        }

        //Do refunding all online invoices
        foreach ($cancelProcess->getOnlinePaidInvoices() as $invoice) {
            try {
                //Create a credit memo from the invoice
                $creditmemo = $cancelProcess->createFullCreditmemoFromInvoice($invoice);

                //We only need to process real credit memos
                if (null === $creditmemo) {
                    continue;
                }

                //If we register the credit memo we call automatically the refund method
                //with the right transaction
                $creditmemo->register();

                //Save the credit memo
                $order->addRelatedObject($creditmemo);

                //Add the success message
                $success[] = $this->_getHelper()->__(
                    "Successfully refund an amount of %s from invoice #%s.",
                    $order->getBaseCurrency()->formatPrecision($creditmemo->getBaseGrandTotal(), 2, array(), false),
                    $invoice->getIncrementId()
                );
            } catch (Exception $e) {
                try {
                    $amount = $cancelProcess->getRefundableAmountFromInvoice($invoice);
                    $errors[] = $this->_getHelper()->__(
                        "Can't refund the amount of %s from invoice #%s. (%s)",
                        $order->getBaseCurrency()->formatPrecision($amount, 2, array(), false),
                        $invoice->getIncrementId(),
                        $e->getMessage()
                    );
                } catch (Exception $innerE) {
                    $errors[] = $this->_getHelper()->__(
                        "Can't refund the invoice #%s.",
                        $invoice->getIncrementId(),
                        $innerE->getMessage()
                    );
                }
            }
        }

        //Get the managers
        $successManagement  = $this->_getHelper()->getPaymentHelper('cancel')->getPaymentErrorManager();
        $errorManagement    = $this->_getHelper()->getPaymentHelper('cancel')->getPaymentErrorManager();

        //No need to convert the message
        $errorManagement
            ->removeHandler('message');

        //Success manager don't need to throw exception and change the session callback
        $successManagement
            ->removeHandler('message')
            ->removeHandler('exception')
            ->updateHandler('session', 'setCallback', 'addSuccess');

        //Process error handling
        if (empty($success) && empty($errors)) {
            $successManagement
                ->updateHandler('session', 'setCallback', 'addNotice')
                ->processMessage("There was no online refunding.");
        } else {
            //Remove the exception handler if we have an success action
            if (!empty($success)) {
                $errorManagement->removeHandler('exception');
            }

            if (!empty($success)) {
                $message = implode('<br/>', $success);
                $successManagement->processMessage($message);
            }

            if (!empty($errors)) {
                $message = implode('<br/>', $errors);
                $errorManagement->processMessage($message);
            }
        }

        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::refund()
     *
     * @param Varien_Object $payment
     * @param unknown_type $amount
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $payment->getOrder();

        //Get the transaction id from the invoice this is the parent id for the creditmemo
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $captureTransaction = $payment->getParentTransactionId();

        //We need a parent id for refunding
        if (empty($captureTransaction)) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage('There is no transaction id for refunding.');
        }

        //Get the transaction model from the id
        /* @var $captureTransaction Mage_Sales_Model_Order_Payment_Transaction */
        $captureTransaction = $payment->getTransaction($captureTransaction);

        //Check for valid type
        if (!$captureTransaction instanceof Mage_Sales_Model_Order_Payment_Transaction) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage(
                    "Can't load the capture transaction (%s).",
                    true,
                    array($payment->getParentTransactionId())
                );
        }

        //Check the type
        if (Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE != $captureTransaction->getTxnType()) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage(
                    'The transaction (%s) is not a capture transaction. Only capture transaction can refund.',
                    true,
                    array($captureTransaction->getTxnId())
                );
        }

        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        $creditmemo = $payment->getCreditmemo();

        if (!$creditmemo instanceof Mage_Sales_Model_Order_Creditmemo) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage('No credit memo available.');
        }

        /* @var $invoice Mage_Sales_Model_Order_Invoice */
        $invoice    = $creditmemo->getInvoice();

        if (!$invoice instanceof Mage_Sales_Model_Order_Invoice) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage('The credit memo has no referenced invoice available.');
        }

        //Collect the already refunded amount in referenced to the invoice
        $alreadyRefunded = 0;
        foreach ($order->getCreditmemosCollection() as $singleCreditmemo) {
            if ($singleCreditmemo->getInvoiceId() == $invoice->getId()) {
                $alreadyRefunded += (float)$singleCreditmemo->getBaseGrandTotal();
            }
        }

        //Close the capture if we have refund the complete amount
        $sumRefunded = $alreadyRefunded + (float)$creditmemo->getBaseGrandTotal();
        $amountdiff  = ((float)$invoice->getBaseGrandTotal() - $sumRefunded);

        if ($this->_getHelper()->getPaymentHelper()->isZeroAmount($amountdiff)) {
            $payment->setShouldCloseParentTransaction(true);
        } else if ($this->_getHelper()->getPaymentHelper()->isNegativeAmount($amountdiff)) {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processMessage(
                    'We only can refund an amount of %s.',
                    true,
                    array(
                        $order->getBaseCurrency()->formatPrecision(
                            $invoice->getBaseGrandTotal() - $alreadyRefunded,
                            2,
                            array(),
                            false
                        )
                    )
                );
        } else {
            $payment->setShouldCloseParentTransaction(false);
        }

        //Don't allow reverse on refunds
        $payment->setIsTransactionClosed(true);

        //Get the request model
        $request = $this->_createRequestModel(__FUNCTION__, $payment);

        //Set the transaction model and the refund amount
        $request
            ->setReferencedTransactionModel($captureTransaction)
            ->setAmount($amount);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );

            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_refund_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('refund')
                ->processErrorCode($request, $this->getCode());
        }

        return $this;
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::void()
     *
     * @param Varien_Object $payment
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        /* @var $order          Mage_Sales_Model_Order */
        /* @var $transaction    Mage_Sales_Model_Order_Payment_Transaction */
        /* @var $invoice        Mage_Sales_Model_Order_Invoice */
        $order          = $this->getOracle()->getModel();
        $transaction    = null;
        $invoice        = null;

        if ($this->_canVoidAuthorization()) {
            $transaction = $payment->getAuthorizationTransaction();
        } elseif ($this->_canVoidCapture()) {
            //Try to resolve the current invoice
            $invoice = $this->_getCurrentInvoice();
            if (!$invoice) {
                $this->_getHelper()->getPaymentHelper()
                    ->getPaymentErrorManager('void')
                    ->processMessage("Can't resolve a referencing invoice.");
            }

            //Get the transaction from he given selected invoice
            $transaction = $payment->getTransaction($invoice->getTransactionId());

            //Now we need to manipulate the transaction informations
            $payment->setParentTransactionId($transaction->getTxnId());
            $payment->setMessage($this->_getHelper()->__("Voided capture."));

            //Check if we can close the parent transaction
            $cancelModel                = $this->getCancelProcessModel($payment);
            $fullRefundAbleAmount       = $cancelModel->getTotalRefundableAmount();
            $invoiceRefundAbleAmount    = $cancelModel->getRefundableAmountFromInvoice($invoice);
            $refundAbleDiff             = $fullRefundAbleAmount - $invoiceRefundAbleAmount;

            //If the difference is zero we can void the full amount of the void able amount
            if ($this->_getHelper()->getPaymentHelper()->isZeroAmount($refundAbleDiff)) {
                $payment->setShouldCloseParentTransaction(true);
            } else {
                $payment->setShouldCloseParentTransaction(false);
            }
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('void')
                ->processMessage("Can't void this order.");
        }

        //Get the request model
        $request = $this->_createRequestModel('reverse', $payment);

        //Set the transaction model
        $request->setReferencedTransactionModel($transaction);

        /* edited by mwe - set amount in request only if invoice exists (order is payed) */
        //Set a amount if needed
        if ($invoice != null) $this->_setVoidAmount($request, $payment, $invoice);

        //Send the capture request to paymentoperator
        $this->_getConnection()->sendRequest($request);

        //Check if the transaction has produce an error
        if (!$request->getResponseModel()->hasError()) {
            //Add transaction information
            $this->_getHelper()->getPaymentHelper()->addTransactionInfoToPayment(
                $payment,
                $request->getResponseModel()
            );


            //If the invoice is not in the registry the user has clicked
            //the void button in the order view and not in the invoice view
            if ($invoice && !$this->_getCurrentInvoiceFromRegistry()) {
                $invoice->cancel();
                $order->addRelatedObject($invoice);
            }

            //Dispatch event success event
            Mage::dispatchEvent("{$this->getCode()}_void_success", array(
                'method'    => $this,
                'request'   => $request,
                'response'  => $request->getResponseModel(),
            ));
        } else {
            $this->_getHelper()->getPaymentHelper()
                ->getPaymentErrorManager('void')
                ->processErrorCode($request, $this->getCode());
        }

        return $this;
    }

    /**
     * This method can use to set a void amount.
     *
     * @param   Dotsource_Paymentoperator_Model_Payment_Request_Request    $request
     * @param   Varien_Object                                       $payment
     * @param  Mage_Sales_Model_Order_Invoice                       $invoice
     */
    protected function _setVoidAmount(
        Dotsource_Paymentoperator_Model_Payment_Request_Request    $request,
        Varien_Object                                       $payment,
        Mage_Sales_Model_Order_Invoice                      $invoice
    )
    {

    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::canVoid()
     *
     * @param Varien_Object $payment
     * @return bool
     */
    public function canVoid(Varien_Object $document)
    {
        $canVoid = parent::canVoid($document);

        if (!$canVoid) {
            return $canVoid;
        }

        return $this->_canVoidAuthorization() || $this->_canVoidCapture();
    }

    /**
     * @see Mage_Payment_Model_Method_Abstract::canCapture()
     *
     * @return bool
     */
    public function canCapture()
    {
        $canCapture = parent::canCapture();

        if (!$canCapture) {
            return $canCapture;
        }

        /* @var $payment Mage_Sales_Model_Order_Payment */
        $payment    = $this->getInfoInstance();

        return $this->_canCaptureAuthorization($payment)
            || $this->canBackendDirectCapture();
    }

    /**
     * Check if we can capture a open authorization.
     *
     * @param   Varien_Object                                       $payment
     * @param   Mage_Sales_Model_Order_Payment_Transaction|mixed    $authorization
     * @return  boolean
     */
    protected function _canCaptureAuthorization($payment, $authorization = null)
    {
        /* @var $payment        Mage_Sales_Model_Order_Payment */
        /* @var $authorization  Mage_Sales_Model_Order_Payment_Transaction */

        //Get the payment
        if (!$payment) {
            $payment = $this->getInfoInstance();
        }

        //Get a authorization
        if (null === $authorization) {
            $authorization = $payment->getAuthorizationTransaction();
        }

        //Check if we can capture a authorization
        return $authorization instanceof Mage_Sales_Model_Order_Payment_Transaction
            && Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH === $authorization->getTxnType()
            && !$authorization->getIsClosed();
    }

    /**
     * Check if we can void an authorization.
     *
     * @param Varien_Object $document
     * @return booleans
     */
    protected function _canVoidAuthorization()
    {
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $payment    = $this->getInfoInstance();
        $order      = $payment->getOrder();

        $authTransaction    = $payment->getAuthorizationTransaction();
        $onlinePaid         = $this->getCancelProcessModel($order)->getTotalRefundableAmount();

        return $authTransaction instanceof Mage_Sales_Model_Order_Payment_Transaction
            && !$authTransaction->getIsClosed()
            && $authTransaction->canVoidAuthorizationCompletely()
            && $this->_getHelper()->getPaymentHelper()->isZeroAmount($onlinePaid);
    }

    /**
     * Check if we can void an single capture.
     *
     * @param Varien_Object $document
     * @return boolean
     */
    protected function _canVoidCapture()
    {
        //Check for a valid invoice first
        $invoice = $this->_getCurrentInvoice();
        if (!$invoice) {
            return false;
        }

        //Check for an voidable capture
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $payment        = $this->getInfoInstance();
        $transaction    = $payment->getTransaction($invoice->getTransactionId());
        if (!$transaction instanceof Mage_Sales_Model_Order_Payment_Transaction
            || $transaction->getIsClosed()
        ) {
            return false;
        }

        //Get the captured amount
        $order          = $payment->getOrder();
        $cancelModel    = $this->getCancelProcessModel($order);
        $onlinePaid     = $cancelModel->getRefundableAmountFromInvoice($invoice);

        if ($this->_getHelper()->getPaymentHelper()->isPositiveAmount($onlinePaid)
            && $this->_getHelper()->getPaymentHelper()->isZeroAmount($order->getBaseTotalOnlineRefunded())
        ) {
            if (!$this->_ignoreDateByVoidCapture) {
                //Check the same date
                return date('Ymd') == date('Ymd', strtotime($invoice->getCreatedAt()));
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the current invoice.
     *
     * @return  Mage_Sales_Model_Order_Invoice
     */
    protected function _getCurrentInvoice()
    {
        $invoice = null;

        //Check the registry first
        if (($invoice = $this->_getCurrentInvoiceFromRegistry())) {
            return $invoice;
        }

        //Get all online paid invoices; if we only have one valid invoice we can use it as current one
        $invoices = $this->getCancelProcessModel($this->getOracle()->getModel())->getOnlinePaidInvoices();
        if ($invoices && 1 === count($invoices)) {
            return array_pop($invoices);
        }

        return null;
    }

    /**
     * Return the current invoice from the registry.
     *
     * @return  Mage_Sales_Model_Order_Invoice|null
     */
    protected function _getCurrentInvoiceFromRegistry()
    {
        return Mage::registry('current_invoice');
    }

    /**
     * Return the amount who will  be refunded by the cancel action.
     *
     * @param Mage_Sales_Model_Order $order
     * @param boolean $formated
     * @return unknown
     */
    public function getCancelAmount(Mage_Sales_Model_Order $order, $formated = true)
    {
        //Get the refund able amount
        $amount = $this->getCancelProcessModel($order)->getTotalRefundableAmount();

        if ($formated) {
            $amount = $order->getBaseCurrency()->formatPrecision($amount, 2, array(), false);
        }

        return $amount;
    }

    /**
     * Return the request model prefix.
     *
     * @return string
     */
    protected function _getRequestModelPath()
    {
        if (is_null($this->_requestModelInfo)) {
            Mage::throwException(
                $this->_getHelper()->__(
                    'The payment method with the code "%s" has a undefined variable _requestModelInfo.',
                    $this->getCode()
                )
            );
        }

        return $this->_requestModelInfo;
    }

    /**
     * Return the default request model prefix.
     *
     * @return string
     */
    protected function _getDefaultRequestModelPath()
    {
        return $this->_requestModelDefault;
    }

    /**
     * Checks if we can create a new capture without a previous
     * authorization and we have all data for that.
     *
     * @return boolean
     */
    public function canBackendDirectCapture()
    {
        return $this->_canBackendDirectCapture;
    }

    /**
     * Set the redirect url. The title is only use in the iframe modus.
     *
     * @param string $url
     * @param boolean $iframe
     * @param string $title
     */
    protected function _setOrderPlaceRedirectUrl($url, $iframe = false, $title = null)
    {
        //check for iframe redirect
        if ($iframe) {
            //Set the given url as iframe url
            $this->_getHelper()
                ->getCheckoutSession(Dotsource_Paymentoperator_Helper_Data::FRONTEND)
                ->setIframeUrl($url);

            //Set the titel
            if (!empty($title)) {
                $this->_getHelper()
                    ->getCheckoutSession(Dotsource_Paymentoperator_Helper_Data::FRONTEND)
                    ->setIframeTitle($title);
            }

            //now override the redirect url to the iframe
            $url = Mage::getUrl(
                'paymentoperator/iframe/show',
                array('_forced_secure' => true)
            );
        }

        //Magento will redirect to this url after order place
        Dotsource_Paymentoperator_Model_Payment_Abstract::$_redirectUrl = $url;
    }

    /**
     * Return the redirect url.
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Dotsource_Paymentoperator_Model_Payment_Abstract::$_redirectUrl;
    }

    /**
     * Return a connection model.
     *
     * @return Dotsource_Paymentoperator_Model_Connection
     */
    protected function _getConnection()
    {
        return Mage::getModel('paymentoperator/connection');
    }

    /**
     * Return true if the current payment model use booking as payment action.
     *
     * @return boolean
     */
    protected function _isPaymentBooking()
    {
        return Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::BOOKING
            == $this->getConfigData('payment_action_paymentoperator');
    }

    /**
     * Return true if the current payment model use authorize as payment action.
     *
     * @return boolean
     */
    protected function _isPaymentAuthorize()
    {
        return Dotsource_Paymentoperator_Model_System_Config_Source_Paymentaction::AUTHORIZE
            == $this->getConfigData('payment_action_paymentoperator');
    }

    /**
     * Check if the payment has ipzone support.
     *
     * @return boolean
     */
    public function useIpZones()
    {
        $zoneAction = $this->getConfigData('allowedipzones');
        $zones      = $this->getConfigData('ipzones');

        return !empty($zoneAction)
            && !empty($zones)
            && Dotsource_Paymentoperator_Model_System_Config_Source_AllowedipzonesAction::ALLOW_ALL_ZONES != $zoneAction;
    }

    /**
     * Check if the payment has country zone support
     *
     * @return boolean
     */
    public function useCountryZones()
    {
        $zoneAction = $this->getConfigData('allowedcountries');
        $zones      = $this->getConfigData('countriesiso3');

        return !empty($zoneAction)
            && !empty($zones)
            && Dotsource_Paymentoperator_Model_System_Config_Source_AllowedcountriesAction::ALLOW_ALL_COUNTRIES != $zoneAction;
    }

    /**
     * Return the ip zones in paymentoperator format.
     * Respects the ip zone action all, specific and restrict.
     *
     * @return string
     */
    public function convertedIpZone()
    {
        //Get the ip zones from the payment
        $zones = $this->getConfigData('ipzones');

        if (empty($zones)) {
            return '';
        }

        //We need the ipzones as array
        $zones = explode(',', $zones);

        if (is_array($zones)) {
            $zoneAction = $this->getConfigData('allowedipzones');

            if ($zoneAction == Dotsource_Paymentoperator_Model_System_Config_Source_AllowedipzonesAction::ALLOW_ANY_ZONES) {
                return implode(',', $zones);
            } elseif ($zoneAction == Dotsource_Paymentoperator_Model_System_Config_Source_AllowedipzonesAction::RESTRICT_ANY_ZONES) {
                return '!'.implode(',!', $zones);
            }
        }

        return '';
    }

    /**
     * Return the ip zones in paymentoperator format.
     * Respects the ip zone action all, specific and restrict.
     *
     * @return string
     */
    public function convertedCountryZone()
    {
        //Get the ip zones from the payment
        $zones = $this->getConfigData('countriesiso3');

        if (empty($zones)) {
            return '';
        }

        //We need the ipzones as array
        $zones = explode(',', $zones);

        if (is_array($zones)) {
            $zoneAction = $this->getConfigData('allowedcountries');

            if ($zoneAction == Dotsource_Paymentoperator_Model_System_Config_Source_AllowedcountriesAction::ALLOW_ANY_COUNTRIES) {
                return implode(',', $zones);
            } elseif ($zoneAction == Dotsource_Paymentoperator_Model_System_Config_Source_AllowedcountriesAction::RESTRICT_ANY_COUNTRIES) {
                return '!'.implode(',!', $zones);
            }
        }

        return '';
    }

    /**
     * Return the cancel process model.
     *
     * @param   Mage_Sales_Model_Order_Payment|Mage_Sales_Model_Order   $payment
     * @return  Dotsource_Paymentoperator_Model_Cancelprocess
     */
    public function getCancelProcessModel($payment)
    {
        //Create the cancel model
        if (null === $this->_cancelProcessModel) {
            //Get the right model
            if ($payment instanceof Mage_Sales_Model_Order) {
                $payment = $payment->getPayment();
            }

            $this->_cancelProcessModel = Mage::getModel('paymentoperator/cancelprocess');
            $this->_cancelProcessModel->setPayment($payment);
        }

        return $this->_cancelProcessModel;
    }

    /**
     * Check for an custom 'order_status'.
     *
     * @param   string  $field
     * @param   integer $storeId
     * @return  mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ('order_status' === $field && $this->hasNewOrderStatus()) {
            return $this->getNewOrderStatus();
        }

        return parent::getConfigData($field, $storeId);
    }

    /**
     * Return all payment informations fields with the conficurations.
     *
     * @return array
     */
    public function getAllPaymentInformationFields()
    {
        return $this->_paymentInformationFields;
    }

    /**
     * Return the payment information model.
     *
     * @return Dotsource_Paymentoperator_Model_Paymentinformation
     */
    public function getPaymentInformationModel()
    {
        //Init the payment information model
        if (null === $this->_paymentInformationModel) {
            $this->_paymentInformationModel = Mage::getModel('paymentoperator/paymentinformation');
            $this->_paymentInformationModel->setPaymentMethod($this);
        }

        return $this->_paymentInformationModel;
    }

    /**
     * Return the last successfully used payment informations.
     *
     * @return Varien_Object|null
     */
    public function getLastPaymentInformation()
    {
        if ($this->hasLastPaymentInformation()) {
            return $this->getPaymentInformationModel()->getPaymentInformation();
        }

        return null;
    }

    /**
     * Return true if there are payment informations that was successfully used.
     *
     * @return boolean
     */
    public function hasLastPaymentInformation()
    {
        //Get old payment information
        $paymentInformation = $this->getPaymentInformationModel()->getPaymentInformation();
        if (!$paymentInformation || !$paymentInformation->hasData()) {
            return false;
        }

        //TODO: Validate the data

        return true;
    }

    /**
     * First it backups the payment informations. After
     * Remove payment informations from the given object.
     *
     * @param   Varien_Object   $payment
     */
    public function removePaymentInformation(Varien_Object $payment)
    {
        //Get all payment fields that should remove
        $paymentFields = $this
            ->getPaymentInformationModel()
            ->getFeaturedPaymentFields(Dotsource_Paymentoperator_Model_Paymentinformation::REMOVE);

        foreach ($paymentFields as $field) {
            $payment->setData($field, null);
        }
    }

    /**
     * Return the oracle.
     *
     * @return Dotsource_Paymentoperator_Model_Oracle_Type_Order
     */
    public function getOracle()
    {
        if (null === $this->_oracle) {
            $this->_oracle = Mage::getModel('paymentoperator/oracle_type_order');
            $this->_oracle->setModel($this);
        }

        return $this->_oracle;
    }

    /**
     * Returns the name/id of an payment field.
     *
     * @param   string  $field
     * @return  string
     */
    public function getFieldName($field)
    {
        return "{$this->getCode()}_{$field}";
    }

    /**
     * Return a container identifier for the given field.
     *
     * @param   string  $field
     * @return  string
     */
    public function getContainerName($field)
    {
        return "container_{$this->getFieldName($field)}";
    }

    /**
     * Return the depends. If depends are not comply the array
     * contains error messages.
     *
     * @return  array
     */
    public function getDepends()
    {
        return array();
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