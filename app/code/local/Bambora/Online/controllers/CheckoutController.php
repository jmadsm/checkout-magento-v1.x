<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
use Bambora_Online_Helper_BamboraConstant as BamboraConstant;
use Bambora_Online_Model_Api_Checkout_Constant_Api as CheckoutApi;

class Bambora_Online_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Bambora_Online_Helper_Data
     */
    private $bamboraHelper;

    public function _construct()
    {
        $this->bamboraHelper = Mage::helper('bambora');
    }

    /**
     * Get Bambora Online Checkout Method
     *
     * @return string
     */
    private function getMethod($order)
    {
        return $order->getPayment()->getMethod();
    }

    /**
     * Get Bambora Online Checkout Method Instance
     *
     * @return Bambora_Online_Model_Checkout_Payment
     */
    private function getMethodInstance($order)
    {
        return $order->getPayment()->getMethodInstance();
    }


    /**
     * Redirect Action
     *
     * @return void
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        try {
            $session->setBamboraCheckoutBamboraQuoteId($session->getQuoteId());

            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order');
            $order = $order->loadByIncrementId($session->getLastRealOrderId());

            $payment = $order->getPayment();
            $pspReference = null;
            if ($payment instanceof Mage_Sales_Model_Order_Payment) {
                $pspReference = $payment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
            }

            $lastSuccessfullQuoteId = $session->getLastSuccessQuoteId();
            if (!empty($pspReference) || empty($lastSuccessfullQuoteId)) {
                $this->_redirect('checkout/cart');
            } else {
                $paymentMethod = $this->getMethodInstance($order);
                $paymentWindow = $paymentMethod->getPaymentWindow($order);
                if (!isset($paymentWindow)) {
                    $this->_redirectUrl($paymentMethod->getCancelUrl());
                } else {
                    $windowState = $paymentMethod->getConfigData(BamboraConstant::WINDOW_STATE, $paymentMethod->getStore()->getStoreId());
                    $paymentData = array(
                        "checkoutToken"=> $paymentWindow->token,
                        "windowState" => $windowState,
                        "headerText"=> $this->bamboraHelper->_s("Thank you for using Bambora Checkout"),
                        "headerText2"=> $this->bamboraHelper->_s("Please wait...")
                        );
                    $this->loadLayout();
                    $block = $this->getLayout()->createBlock('bambora/checkout_redirect', 'bamboraredirect', $paymentData);
                    $this->getLayout()->getBlock('content')->append($block);
                    $this->renderLayout();
                }
            }
        }
        catch (Exception $e) {
            $session->addError($this->bamboraHelper->_s("An error occured. Please try again!"));
            Mage::logException($e);
            $this->_redirect("bambora/checkout/cancel");
        }
    }

    /**
     * Accept Action
     */
    public function acceptAction()
    {
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure'=>$this->getRequest()->isSecure()));
    }

    /**
     * Cancel Action
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $cart = Mage::getSingleton('checkout/cart');
        $larstOrderId = $session->getLastRealOrderId();
        $order = Mage::getModel('sales/order')->loadByIncrementId($larstOrderId);
        if ($order->getId()) {
            /** @var Mage_Sales_Model_Order_Payment */
            $orderPayment = $order->getPayment();
            $pspReference = $orderPayment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
            if(empty($pspReference)){
                $session->getQuote()->setIsActive(0)->save();
                $session->clear();
                try {
                    $order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
                    $order->cancel()->save();
                }
                catch (Mage_Core_Exception $e) {
                    Mage::logException($e);
                }
                $items = $order->getItemsCollection();
                foreach ($items as $item) {
                    try {
                        $cart->addOrderItem($item);
                    }
                    catch (Mage_Core_Exception $e) {
                        $session->addError($this->__($e->getMessage()));
                        Mage::logException($e);
                        continue;
                    }
                }
                $cart->save();
            }
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Callback action
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function callbackAction()
    {
        $message ='';
        $responseCode = '400';
        $transactionResponse = null;
        $order = null;
        if ($this->validateCallback($message, $transactionResponse, $order)) {
            $message = $this->processCallback($transactionResponse, $order, $responseCode);
        } else {
            if (isset($order) && $order->getId()) {
                $order->addStatusHistoryComment("Callback from Bambora returned with an error: ". $message);
                $order->save();
            }
        }

        $response = $this->getResponse()->setHeader('HTTP/1.0', $responseCode, true)
            ->setHeader('Content-type', 'application/json', true)
            ->setHeader('X-EPay-System', $this->bamboraHelper->getCmsInfo())
            ->setBody($message);

        return $response;
    }

    /**
     * Validate the callback
     *
     * @param string $message
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transactionResponse
     * @param Mage_Sales_Model_Order $order
     * @return boolean
     */
    private function validateCallback(&$message, &$transactionResponse, &$order)
    {
        try {
            $txnId = $this->getRequest()->getParam('txnid');
            if (!isset($txnId)) {
                $message = "No txnid was supplied to the system!";
                return false;
            }
            $orderId = $this->getRequest()->getParam('orderid');
            if (!isset($orderId)) {
                $message = "No orderid was supplied to the system!";
                return false;
            }
            $amount = $this->getRequest()->getParam('amount');
            if (!isset($amount)) {
                $message = "No amount supplied to the system!";
                return false;
            }
            $currency = $this->getRequest()->getParam('currency');
            if (!isset($currency)) {
                $message = "No GET(currency) supplied to the system!";
                return false;
            }
            /** @var Mage_Sales_Model_Order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
            if (!isset($order)) {
                $message = "The order object could not be loaded";
                return false;
            }
            if ($order->getIncrementId() != $orderId) {
                $message = "The loaded order id does not match the callback GET(orderId)";
                return false;
            }
            if ($this->getMethod($order) !== Bambora_Online_Model_Checkout_Payment::METHOD_CODE) {
                $message = "The Payment method of the order dont match. Order method: ". $order->getPayment()->getMethod();
                return false;
            }

            $method = $this->getMethodInstance($order);
            $storeId = $order->getStoreId();
            $storeMd5 = $method->getConfigData(BamboraConstant::MD5_KEY, $storeId);
            if (!empty($storeMd5)) {
                $accept_params = $this->getRequest()->getParams();
                $var = "";
                foreach ($accept_params as $key => $value) {
                    if ($key != "hash") {
                        $var .= $value;
                    }
                }

                $storeHash = md5($var . $storeMd5);
                $hash = $this->getRequest()->getParam('hash');
                if ($storeHash != $hash) {
                    $message = "Hash validation failed - Please check your MD5 key";
                    return false;
                }
            }

            //Validate Transaction
			$storeId = $order->getStoreId();
            $apiKey = $method->getApiKey($storeId);

            /** @var Bambora_Online_Model_Api_Checkout_Merchant */
            $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);

            $transactionResponse = $merchantApi->getTransaction($txnId, $apiKey);

            //Validate transaction
            $meassage = "";
            if (!$this->bamboraHelper->validateCheckoutApiResult($transactionResponse, $txnId, true, $meassage)) {
                return false;
            }
        }
        catch (Exception $ex) {
            $message = $ex->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Process the callback
     *
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transactionResponse
     * @param Mage_Sales_Model_Order $order
     * @param string $responseCode
     */
    private function processCallback($transactionResponse, $order, &$responseCode)
    {
        $message = '';
        $payment = $order->getPayment();
        try {
            $pspReference = $payment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
            if (empty($pspReference) && !$order->isCanceled()) {
                $method = $this->getMethodInstance($order);
                $storeId = $order->getStoreId();

                $this->updatePaymentData($order, $method->getConfigData(BamboraConstant::ORDER_STATUS_AFTER_PAYMENT, $storeId), $transactionResponse);
                $feeAmount = $transactionResponse->transaction->total->feeamount;
                if ((int)$method->getConfigData(BamboraConstant::ENABLE_SURCHARGE, $storeId) == 1 && isset($feeAmount) && (int)($feeAmount) > 0) {
                    $this->addSurchargeToOrder($order, $transactionResponse, $method);
                }

                if ((int)$method->getConfigData(BamboraConstant::SEND_MAIL_ORDER_CONFIRMATION, $storeId) == 1) {
                    $this->sendOrderEmail($order);
                }

                if ((int)$method->getConfigData(BamboraConstant::INSTANT_INVOICE, $storeId) == 1) {
                    $this->createInvoice($order);
                }
                $message = "Callback Success - Order created";
            } else {
                if ($order->isCanceled()) {
                    $message = "Callback Success - Order was canceled by Magento";
                } else {
                    $message = "Callback Success - Order already created";
                }
            }
            $responseCode = '200';
        }
        catch (Exception $e) {
            Mage::logException($e);
            $message = "Callback Failed: " .$e->getMessage();
            $order->addStatusHistoryComment($message);
            $payment->setAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE, "");
            $payment->save();
            $order->save();
            $responseCode = '500';
        }

        return $message;
    }

    /**
     * Update the payment data
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $orderStatusAfterPayment
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transactionResponse
     */
    private function updatePaymentData($order, $orderStatusAfterPayment, $transactionResponse)
    {
        try {
            $payment = $order->getPayment();
            $txnId = $transactionResponse->transaction->id;
            $payment->setTransactionId($txnId);
            $payment->setIsTransactionClosed(false);
            $payment->setAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE, $txnId);
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
            $payment->setCcType($transactionResponse->transaction->information->paymentTypes[0]->displayName);

            $truncatedCardNumber = $transactionResponse->transaction->information->primaryAccountnumbers[0]->number;
            $truncatedCardNumberEncrypted = Mage::helper('core')->encrypt($truncatedCardNumber);
            $payment->setCcNumberEnc($truncatedCardNumberEncrypted);

            $isInstantCapture = false;
            if($transactionResponse->transaction->total->authorized === $transactionResponse->transaction->total->captured) {
                $isInstantCapture = true;
            }

            $payment->setAdditionalInformation(BamboraConstant::INSTANT_CAPTURE, $isInstantCapture);

            $payment->save();

            $message = $this->bamboraHelper->_s("Payment authorization was a success.") . ' ' . $this->bamboraHelper->_s("Transaction ID").': '.$txnId;
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatusAfterPayment, $message, false);
            $order->save();
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Add Surcharge item to the order as a order line
     *
     * @param Mage_Sales_Model_Order $order
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transactionResponse
     * @param Bambora_Online_Model_Checkout_Payment $method
     * @return void
     */
    private function addSurchargeToOrder($order, $transactionResponse, $method)
    {
        try {
            $baseFeeAmount = (float)$this->bamboraHelper->convertPriceFromMinorunits($transactionResponse->transaction->total->feeamount, $transactionResponse->transaction->currency->minorunits);
            $feeAmount = Mage::helper('directory')->currencyConvert($baseFeeAmount, $order->getBaseCurrencyCode(), $order->getOrderCurrencyCode());

            foreach ($order->getAllItems() as $item) {
                if ($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE) {
                    return;
                }
            }

            $text = $transactionResponse->transaction->information->paymentTypes[0]->displayName . ' - ' . $this->bamboraHelper->_s('Surcharge fee');
            $storeId = $order->getStoreId();

            if ($method->getConfigData(BamboraConstant::SURCHARGE_MODE) === BamboraConstant::SURCHARGE_ORDER_LINE) {
                /** @var Mage_Sales_Model_Order_Item */
                $feeItem = $this->bamboraHelper->createFeeItem($baseFeeAmount, $feeAmount, $storeId, $order->getId(), $text);
                $order->addItem($feeItem);
                $order->setBaseSubtotal($order->getBaseSubtotal() + $baseFeeAmount);
                $order->setBaseSubtotalInclTax($order->getBaseSubtotalInclTax() + $baseFeeAmount);
                $order->setSubtotal($order->getSubtotal() + $feeAmount);
                $order->setSubtotalInclTax($order->getSubtotalInclTax() + $feeAmount);
            } else {
                //Add fee to shipment
                $order->setBaseShippingAmount($order->getBaseShippingAmount() + $baseFeeAmount);
                $order->setBaseShippingInclTax($order->getBaseShippingInclTax() + $baseFeeAmount);
                $order->setShippingAmount($order->getShippingAmount() + $feeAmount);
                $order->setShippingInclTax($order->getShippingInclTax() + $feeAmount);
            }

            $order->setBaseGrandTotal($order->getBaseGrandTotal() + $baseFeeAmount);
            $order->setGrandTotal($order->getGrandTotal() + $feeAmount);

            $feeMessage = $text . ' ' .$this->bamboraHelper->_s("added to order");
            $order->addStatusHistoryComment($feeMessage);
            $order->save();
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Send an order confirmation to the customer
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function sendOrderEmail($order)
    {
        try {
            $order->sendNewOrderEmail();
            $order->setIsCustomerNotified(1);
            $order->addStatusHistoryComment(sprintf($this->bamboraHelper->_s("Notified customer about order #%s"), $order->getIncrementId()))
                ->setIsCustomerNotified(true);
            $order->save();
        }
        catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create an invoice
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function createInvoice($order)
    {
        try {
            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();

                $transactionSave = Mage::getModel('core/resource_transaction')
                  ->addObject($invoice)
                  ->addObject($invoice->getOrder());
                $transactionSave->save();

                $method = $this->getMethodInstance($order);
                if ((int)$method->getConfigData(BamboraConstant::INSTANT_INVOICE_MAIL, $order->getStoreId()) == 1) {
                    $invoice->sendEmail();
                    $order->addStatusHistoryComment(sprintf($this->bamboraHelper->_s("Notified customer about invoice #%s"), $invoice->getId()))
                        ->setIsCustomerNotified(true);
                    $order->save();
                }
            }
        }
        catch (Exception $e) {
            throw $e;
        }
    }
}
