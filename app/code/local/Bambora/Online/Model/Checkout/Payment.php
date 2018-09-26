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
use Bambora_Online_Model_Api_Checkout_Constant_Api as CheckoutApi;
use Bambora_Online_Helper_BamboraConstant as BamboraConstant;
use Bambora_Online_Model_Api_Checkout_Constant_Model as CheckoutApiModel;

class Bambora_Online_Model_Checkout_Payment extends Mage_Payment_Model_Method_Abstract
{
    const METHOD_CODE = 'bamboracheckout';
    const PSP_REFERENCE = 'bamboraCheckoutReference';

    protected $_code = self::METHOD_CODE;
    protected $_formBlockType = 'bambora/checkout_form';
    protected $_infoBlockType = 'bambora/checkout_info';

    /**
     * Payment Method feature
     */
    protected $_isGateway               = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canOrder                = true;
    protected $_canVoid                 = true;

    public function canEdit()
    {
        return false;
    }

    /**
     * @var Bambora_Online_Helper_Data
     */
    protected $bamboraHelper;

    // Default constructor
    //
    public function __construct()
    {
        $this->bamboraHelper = Mage::helper('bambora');
    }

    /**
     * Retrieve an api key for the Bambora Api
     *
     * @return string
     */
    public function getApiKey($storeId = null)
    {
			$storeId = isset($storeId) ?  $storeId : $this->getStore()->getId();

            $accesstoken = $this->getConfigData(BamboraConstant::ACCESS_TOKEN, $storeId);
            $merchantNumber = $this->getConfigData(BamboraConstant::MERCHANT_NUMBER, $storeId);
            $secrettokenCrypt = $this->getConfigData(BamboraConstant::SECRET_TOKEN, $storeId);
            $secrettoken = Mage::helper('core')->decrypt($secrettokenCrypt);

            $combined = $accesstoken . '@' . $merchantNumber .':'. $secrettoken;
            $encodedKey = base64_encode($combined);

			return 'Basic '.$encodedKey;
    }

    /**
     * Retrieve allowed PaymentCardIds
     *
     * @param $currency
     * @param $amount
     * @return array
     */
    public function getPaymentCardIds($currency = null, $amount = null)
    {
        if (!isset($currency)) {
            $currency = $this->getQuote()->getBaseCurrencyCode();
        }

        if (!isset($amount)) {
            $amount = $this->getQuote()->getBaseGrandTotal();
        }

        $minorunits = $this->bamboraHelper->getCurrencyMinorunits($currency);
        $amountMinorunits = $this->bamboraHelper->convertPriceToMinorunits($amount, $minorunits, $this->getConfigData(BamboraConstant::ROUNDING_MODE));

        /** @var Bambora_Online_Model_Api_Checkout_Merchant */
        $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);
        $paymentTypeResponse = $merchantApi->getPaymentTypes($currency, $amountMinorunits, $this->getApiKey());

        $message = "";
        if ($this->bamboraHelper->validateCheckoutApiResult($paymentTypeResponse, $this->getQuote()->getId(), false, $message)) {
            $paymentCardIdsArray = array();

            foreach ($paymentTypeResponse->paymentCollections as $payment) {
                foreach ($payment->paymentGroups as $group) {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
            return $paymentCardIdsArray;
        } else {
            $this->adminMessageHandler()->addError($message);
            return null;
        }
    }

    /**
     * Get Bambora Checkout payment window
     *
	 * @param  Mage_Sales_Model_Order $order
     * @return Bambora_Online_Model_Api_Checkout_Response_Checkout
     */
    public function getPaymentWindow($order)
    {
        $checkoutRequest = $this->createCheckoutRequest();
		$storeId = $order->getStoreId();
        /** @var Bambora_Online_Model_Api_Checkout_Checkout */
        $checkoutApi = Mage::getModel(CheckoutApi::API_CHECKOUT);
        $checkoutResponse = $checkoutApi->setCheckout($checkoutRequest, $this->getApiKey($storeId));

        $message = "";
        if (!$this->bamboraHelper->validateCheckoutApiResult($checkoutResponse, $checkoutRequest->order->ordernumber, false, $message)) {
            $this->frontMessageHandler()->addError($this->bamboraHelper->_s('The order could not be created - The payment window could not be retrived'));
            $checkoutResponse = null;
        }

        return $checkoutResponse;
    }

    /**
     * Create the Bambora Checkout Request object
     *
     * @param  Mage_Sales_Model_Order $order
     * @return Bambora_Online_Model_Api_Checkout_Request_Checkout
     */
    public function createCheckoutRequest($order = null)
    {
        if (!isset($order)) {
            $order = $this->getOrder();
        }

        $billingAddress  = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if ($order->getBillingAddress()->getEmail()) {
            $email = $order->getBillingAddress()->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }

        $storeId = $order->getStoreId();
        $minorunits = $this->bamboraHelper->getCurrencyMinorUnits($order->getBaseCurrencyCode());
        $roundingMode =  $this->getConfigData(BamboraConstant::ROUNDING_MODE);

        $totalAmountMinorUnits = $this->bamboraHelper->convertPriceToMinorunits($order->getBaseTotalDue(), $minorunits, $roundingMode);

        /** @var Bambora_Online_Model_Api_Checkout_Request_Checkout */
        $checkoutRequest = Mage::getModel(CheckoutApiModel::REQUEST_CHECKOUT);
        $checkoutRequest->instantcaptureamount = intval($this->getConfigData(BamboraConstant::INSTANT_CAPTURE, $storeId)) === 0 ? 0 : $totalAmountMinorUnits;
        $checkoutRequest->language = $this->bamboraHelper->getShopLocalCode();
        $checkoutRequest->paymentwindowid = intval($this->getConfigData(BamboraConstant::PAYMENT_WINDOW_ID, $storeId));

        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Customer */
        $bamboraCustomer = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_CUSTOMER);
        $bamboraCustomer->email = $email;

        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Order */
        $bamboraOrder = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_ORDER);
        $bamboraOrder->currency = $order->getBaseCurrencyCode();
        $bamboraOrder->ordernumber = $order->getIncrementId();
        $bamboraOrder->total = $totalAmountMinorUnits;
        $bamboraOrder->vatamount = $this->bamboraHelper->convertPriceToMinorunits($order->getBaseTaxAmount(), $minorunits, $roundingMode);

        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Url */
        $bamboraUrl = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_URL);
        $bamboraUrl->accept = $this->getAcceptUrl();
        $bamboraUrl->decline =  $this->getCancelUrl();

        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Callback */
        $bamboraCallback = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_CALLBACK);
        $bamboraCallback->url = $this->getCallbackUrl();
        $bamboraUrl->callbacks = array();
        $bamboraUrl->callbacks[] = $bamboraCallback;
        $bamboraUrl->immediateredirecttoaccept = intval($this->getConfigData(BamboraConstant::IMMEDIATEREDI_REDIRECT_TO_ACCEPT, $storeId));
        $checkoutRequest->url = $bamboraUrl;

        if ($billingAddress) {
            $bamboraCustomer->phonenumber = $billingAddress->getTelephone();
            $bamboraCustomer->phonenumbercountrycode = $billingAddress->getCountryId();

            /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Address */
            $bamboraBillingAddress = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_ADDRESS);
            $bamboraBillingAddress->att = "";
            $bamboraBillingAddress->city = $billingAddress->getCity();
            $bamboraBillingAddress->country = $billingAddress->getCountryId();
            $bamboraBillingAddress->firstname = $billingAddress->getFirstname();
            $bamboraBillingAddress->lastname = $billingAddress->getLastname();
            $bamboraBillingAddress->street = $billingAddress->getStreet(-1);
            $bamboraBillingAddress->zip = $billingAddress->getPostcode();

            $bamboraOrder->billingaddress = $bamboraBillingAddress;
        }

        if ($shippingAddress) {
            /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Address */
            $bamboraShippingAddress = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_ADDRESS);
            $bamboraShippingAddress->att = "";
            $bamboraShippingAddress->city = $shippingAddress->getCity();
            $bamboraShippingAddress->country = $shippingAddress->getCountryId();
            $bamboraShippingAddress->firstname = $shippingAddress->getFirstname();
            $bamboraShippingAddress->lastname = $shippingAddress->getLastname();
            $bamboraShippingAddress->street = $shippingAddress->getStreet(-1);
            $bamboraShippingAddress->zip = $shippingAddress->getPostcode();

            $bamboraOrder->shippingaddress = $bamboraShippingAddress;
        }

        $checkoutRequest->customer = $bamboraCustomer;

        $bamboraOrderLines = array();
        /** @var Mage_Sales_Model_Order_Item[] */
        $items = $order->getAllVisibleItems();
        $lineNumber = 1;
        foreach ($items as $item) {
            /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Line */
            $invoiceItem = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
            $invoiceItem->description = $item->getName();
            $invoiceItem->id = $item->getSku();
            $invoiceItem->linenumber = $lineNumber;
            $invoiceItem->quantity = floatval($item->getQtyOrdered());
            $invoiceItem->text = $item->getName();
            $invoiceItem->totalprice = $this->bamboraHelper->convertPriceToMinorunits($item->getBasePrice(), $minorunits, $roundingMode);
            $invoiceItem->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($item->getBasePriceInclTax(), $minorunits, $roundingMode);
            $invoiceItem->totalpricevatamount = $this->bamboraHelper->convertPriceToMinorunits($item->getBaseTaxAmount(), $minorunits, $roundingMode);
            $invoiceItem->unit = $this->bamboraHelper->_s("pcs.");
            $invoiceItem->vat = floatval($item->getTaxPercent());

            $bamboraOrderLines[] = $invoiceItem;
            $lineNumber++;
        }
        // Add Shipment
        $baseShippingAmount = $order->getBaseShippingAmount();
        if ($baseShippingAmount != 0) {
            $shippingDescription = $order->getShippingDescription();
            /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Line */
            $invoiceShipping = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
            $invoiceShipping->description = isset($shippingDescription) ? $shippingDescription : $this->bamboraHelper->_s("shipping");
            $invoiceShipping->id = $this->bamboraHelper->_s("shipping");
            $invoiceShipping->linenumber = $lineNumber;
            $invoiceShipping->quantity = 1;
            $invoiceShipping->text = isset($shippingDescription) ? $shippingDescription : $this->bamboraHelper->_s("shipping");
            $invoiceShipping->totalprice = $this->bamboraHelper->convertPriceToMinorunits($baseShippingAmount, $minorunits, $roundingMode);
            $invoiceShipping->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($order->getShippingInclTax(), $minorunits, $roundingMode);
            $invoiceShipping->totalpricevatamount = $this->bamboraHelper->convertPriceToMinorunits($order->getShippingTaxAmount(), $minorunits, $roundingMode);
            $invoiceShipping->unit = $this->bamboraHelper->_s("pcs.");

            $shippingTaxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class');
            $shippingTaxPercent = $this->bamboraHelper->getTaxRate($order, $shippingTaxClass);
            $invoiceShipping->vat =  $shippingTaxPercent;

            $bamboraOrderLines[] = $invoiceShipping;
            $lineNumber++;
        }

        // Add Discount
        $baseDiscountAmount = $order->getBaseDiscountAmount();
        if ($baseDiscountAmount != 0) {
            $discountDescription = $order->getDiscountDescription();
            $invoiceDiscount = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
            $invoiceDiscount->description = isset($discountDescription) ? $discountDescription : $this->bamboraHelper->_s("discount");
            $invoiceDiscount->id = $this->bamboraHelper->_s("discount");
            $invoiceDiscount->linenumber = $lineNumber;
            $invoiceDiscount->quantity = 1;
            $invoiceDiscount->text = $order->getDiscountDescription();
            $invoiceDiscount->totalprice = $this->bamboraHelper->convertPriceToMinorunits($baseDiscountAmount, $minorunits, $roundingMode);
            $invoiceDiscount->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($baseDiscountAmount, $minorunits, $roundingMode);
            $invoiceDiscount->totalpricevatamount = 0;
            $invoiceDiscount->unit = $this->bamboraHelper->_s("pcs.");
            $invoiceDiscount->vat =  0;

            $bamboraOrderLines[] = $invoiceDiscount;
        }
        $bamboraOrder->lines = $bamboraOrderLines;
        $checkoutRequest->order = $bamboraOrder;

        return $checkoutRequest;
    }

    /**{@inheritDoc}*/
    public function capture(Varien_Object $payment, $amount)
    {
        try {
            $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
            $isInstantCapure = $payment->getAdditionalInformation(BamboraConstant::INSTANT_CAPTURE);

            if ($isInstantCapure === true) {
                $payment->setTransactionId($transactionId . '-' . BamboraConstant::INSTANT_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

                return $this;
            }

            if (!$this->canOnlineAction($payment)) {
                throw new Exception($this->bamboraHelper->_s("The capture action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            /** @var Mage_Sales_Model_Order */
            $order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->bamboraHelper->getCurrencyMinorunits($currency);

            /** @var Bambora_Online_Model_Api_Checkout_Request_Capture $captureRequest */
            $captureRequest = Mage::getModel(CheckoutApiModel::REQUEST_CAPTURE);
            $captureRequest->amount = $this->bamboraHelper->convertPriceToMinorunits($amount, $minorunits, $this->getConfigData(BamboraConstant::ROUNDING_MODE));
            $captureRequest->currency = $currency;

            //Only add invoice lines if it is a full capture
            $invoiceLines = null;
            if (floatval($amount) === floatval($order->getBaseTotalDue())) {
                $invoiceLines = $this->getCaptureInvoiceLines($order);
            }


            $captureRequest->invoicelines = $invoiceLines;
			$storeId = $order->getStoreId();
            /** @var Bambora_Online_Model_Api_Checkout_Transaction */
            $transactionApi = Mage::getModel(CheckoutApi::API_TRANSACTION);
            $captureResponse = $transactionApi->capture($transactionId, $captureRequest, $this->getApiKey($storeId));

            $message = "";
            if (!$this->bamboraHelper->validateCheckoutApiResult($captureResponse, $order->getIncrementId(), true, $message)) {
                throw new Exception($this->bamboraHelper->_s('The capture action failed.') . ' - '.$message);
            }

            $transactionoperationId = "";
            foreach ($captureResponse->transactionOperations as $transactionoperation) {
                $transactionoperationId = $transactionoperation->id;
            }

            $payment->setTransactionId($transactionoperationId . '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
            return null;
        }
    }

    /**{@inheritDoc}*/
    public function refund(Varien_Object $payment, $amount)
    {
        try {
            if (!$this->canOnlineAction($payment)) {
                throw new Exception($this->bamboraHelper->_s("The refund action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);

			/** @var Mage_Sales_Model_Order */
			$order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->bamboraHelper->getCurrencyMinorunits($currency);

            /** @var Bambora_Online_Model_Api_Checkout_Request_Credit */
            $creditRequest = Mage::getModel(CheckoutApiModel::REQUEST_CREDIT);
            $creditRequest->amount = $this->bamboraHelper->convertPriceToMinorunits($amount, $minorunits, $this->getConfigData(BamboraConstant::ROUNDING_MODE));
            $creditRequest->currency = $currency;

            $creditMemo = $payment->getCreditmemo();
            $creditRequest->invoicelines = $this->getRefundInvoiceLines($creditMemo, $order);

			$storeId = $order->getStoreId();
            /** @var Bambora_Online_Model_Api_Checkout_Transaction */
            $transactionApi = Mage::getModel(CheckoutApi::API_TRANSACTION);
            $creditResponse = $transactionApi->credit($transactionId, $creditRequest, $this->getApiKey($storeId));
            $message = "";
            if (!$this->bamboraHelper->validateCheckoutApiResult($creditResponse, $order->getIncrementId(), true, $message)) {
                throw new Exception($this->bamboraHelper->_s("The refund action failed.") . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach ($creditResponse->transactionOperations as $transactionoperation) {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
            return null;
        }
    }

    /**{@inheritDoc}*/
    public function cancel(Varien_Object $payment)
    {
        try {
            $this->void($payment);
            $this->adminMessageHandler()->addSuccess($this->bamboraHelper->_s("The payment have been voided").' ('.$payment->getOrder()->getIncrementId() .')');
        } catch (Exception $e) {
            $this->adminMessageHandler()->addError($e->getMessage());
        }

        return $this;
    }

    /**{@inheritDoc}*/
    public function void(Varien_Object $payment)
    {
        try {
            if (!$this->canOnlineAction($payment)) {
                throw new Exception($this->bamboraHelper->_s("The void action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);

			/** @var Mage_Sales_Model_Order */
			$order = $payment->getOrder();
			$storeId = $order->getStoreId();
            /** @var Bambora_Online_Model_Api_Checkout_Transaction */
            $transactionApi = Mage::getModel(CheckoutApi::API_TRANSACTION);
            $deleteResponse = $transactionApi->delete($transactionId, $this->getApiKey($storeId));
            $message = "";
            if (!$this->bamboraHelper->validateCheckoutApiResult($deleteResponse, $order->getIncrementId(), true, $message)) {
                throw new Exception($this->bamboraHelper->_s("The void action failed.") . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach ($deleteResponse->transactionOperations as $transactionoperation) {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
            return null;
        }
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param string $transactionId
	 * @param Mage_Sales_Model_Order $order
     * @return Bambora_Online_Model_Api_Checkout_Response_Transaction|null
     * @throws Exception
     */
    public function getTransaction($transactionId, $order)
    {
        $transaction = null;
        try {
            /** @var Bambora_Online_Model_Api_Checkout_Merchant */
            $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);
			$storeId = $order->getStoreId();
            $transactionResponse = $merchantApi->getTransaction($transactionId, $this->getApiKey($storeId));

            $message = "";
			$orderId = $order->getIncrementId();
            if (!$this->bamboraHelper->validateCheckoutApiResult($transactionResponse, $orderId, true, $message)) {
                throw new Exception($this->bamboraHelper->_s("The Get Transaction action failed.") . ' - '.$message);
            }
            $transaction = $transactionResponse->transaction;
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        return $transaction;
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param string $transactionId
     * @param Mage_Sales_Model_Order $order
     * @param bool $showError
     * @return Bambora_Online_Model_Api_Checkout_Response_Transaction|null
     * @throws Exception
     */
    public function getTransactionOperations($transactionId, $order)
    {
        $transactionOperations = null;
        try {
			$storeId = $order->getStoreId();
			$orderId = $order->getIncrementId();
            /** @var Bambora_Online_Model_Api_Checkout_Merchant */
            $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);
            $listTransactionOperationsResponse = $merchantApi->getTransactionOperations($transactionId, $this->getApiKey($storeId));

            $message = "";
            if (!$this->bamboraHelper->validateCheckoutApiResult($listTransactionOperationsResponse, $orderId, true, $message)) {
                throw new Exception($this->bamboraHelper->_s("The List TransactionOperations action failed.") . ' - '.$message);
            }
            $transactionOperations = $listTransactionOperationsResponse->transactionOperations;
        } catch (Exception $e) {
            Mage::throwException($e->getMessage());
        }

        return $transactionOperations;
    }

    /**
     * Get Refund Invoice Lines
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order $order
     * @return Bambora_Online_Model_Api_Checkout_Request_Model_Line[]
     */
    public function getCaptureInvoiceLines($order)
    {
        $invoice = $order->getInvoiceCollection()->getLastItem();
        $invoiceItems = $order->getAllVisibleItems();
        $lines = array();
        $feeItem = null;
        foreach ($invoiceItems as $item) {
            if ($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE) {
                $feeItem = $this->createInvoiceLineFromInvoice($item, $order);
                continue;
            }
            $lines[] = $this->createInvoiceLineFromInvoice($item, $order);
        }

        $minorunits = $this->bamboraHelper->getCurrencyMinorunits($invoice->getBaseCurrencyCode());
        $roundingMode = $this->getConfigData(BamboraConstant::ROUNDING_MODE);

        // Add Shipment
        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Line */
        $invoiceShipping = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
        $invoiceShipping->description = $order->getShippingDescription();
        $invoiceShipping->id = $this->bamboraHelper->_s("shipping");
        $invoiceShipping->linenumber = count($lines) + 1;
        $invoiceShipping->quantity = 1;
        $invoiceShipping->text = $order->getShippingDescription();
        $invoiceShipping->totalprice = $this->bamboraHelper->convertPriceToMinorunits($invoice->getBaseShippingAmount(), $minorunits, $roundingMode);
        $invoiceShipping->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($invoice->getShippingInclTax(), $minorunits, $roundingMode);
        $invoiceShipping->totalpricevatamount = $this->bamboraHelper->convertPriceToMinorunits($invoice->getShippingTaxAmount(), $minorunits, $roundingMode);
        $invoiceShipping->unit = $this->bamboraHelper->_s("pcs.");

        $shippingTaxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class');
        $shippingTaxPercent = $this->bamboraHelper->getTaxRate($invoice, $shippingTaxClass);
        $invoiceShipping->vat =  $shippingTaxPercent;

        $lines[] = $invoiceShipping;


        // Add Discount
        $invoiceDiscount = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
        $invoiceDiscount->description = $invoice->getDiscountDescription();
        $invoiceDiscount->id = $this->bamboraHelper->_s("discount");
        $invoiceDiscount->linenumber = count($lines) + 1;
        $invoiceDiscount->quantity = 1;
        $invoiceDiscount->text = $invoice->getDiscountDescription();
        $invoiceDiscount->totalprice = $this->bamboraHelper->convertPriceToMinorunits($invoice->getBaseDiscountAmount(), $minorunits, $roundingMode);
        $invoiceDiscount->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($invoice->getBaseDiscountAmount(), $minorunits, $roundingMode);
        $invoiceDiscount->totalpricevatamount = 0;
        $invoiceDiscount->unit = $this->bamboraHelper->_s("pcs.");
        $invoiceDiscount->vat =  0;

        $lines[] = $invoiceDiscount;

        //Add fee item
        if (isset($feeItem)) {
            $lines[] = $feeItem;
        }

        return $lines;
    }

    /**
     * Get Refund Invoice Lines
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditMemo
     * @param Mage_Sales_Model_Order $order
     * @return Bambora_Online_Model_Api_Checkout_Request_Model_Line[]
     */
    public function getRefundInvoiceLines($creditMemo, $order)
    {
        $lines = array();
        //Fee item must be after shipment to keep the orginal authorize order of items
        $feeItem = null;
        $items = $this->filterVisibleItemsOnly($creditMemo->getAllItems());

        foreach ($items as $item) {
            if ($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE) {
                $feeItem = $this->createInvoiceLineFromInvoice($item, $order);
                continue;
            }
            $lines[] = $this->createInvoiceLineFromInvoice($item, $order);
        }
        $minorunits = $this->bamboraHelper->getCurrencyMinorunits($creditMemo->getBaseCurrencyCode());
        $roundingMode = $this->getConfigData(BamboraConstant::ROUNDING_MODE);

        // Add Shipment
        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Line */
        $invoiceShipping = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
        $invoiceShipping->description = $creditMemo->getShippingDescription();
        $invoiceShipping->id = $this->bamboraHelper->_s("shipping");
        $invoiceShipping->linenumber = count($lines) + 1;
        $invoiceShipping->quantity = 1;
        $invoiceShipping->text = $creditMemo->getShippingDescription();
        $invoiceShipping->totalprice = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getBaseShippingAmount(), $minorunits, $roundingMode);
        $invoiceShipping->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getShippingInclTax(), $minorunits, $roundingMode);
        $invoiceShipping->totalpricevatamount = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getShippingTaxAmount(), $minorunits, $roundingMode);
        $invoiceShipping->unit = $this->bamboraHelper->_s("pcs.");

        $shippingTaxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class');
        $shippingTaxPercent = $this->bamboraHelper->getTaxRate($creditMemo, $shippingTaxClass);
        $invoiceShipping->vat =  $shippingTaxPercent;

        $lines[] = $invoiceShipping;


        // Add Discount
        $invoiceDiscount = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
        $invoiceDiscount->description = $creditMemo->getDiscountDescription();
        $invoiceDiscount->id = $this->bamboraHelper->_s("discount");
        $invoiceDiscount->linenumber = count($lines) + 1;
        $invoiceDiscount->quantity = 1;
        $invoiceDiscount->text = $creditMemo->getDiscountDescription();
        $invoiceDiscount->totalprice = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getBaseDiscountAmount(), $minorunits, $roundingMode);
        $invoiceDiscount->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getBaseDiscountAmount(), $minorunits, $roundingMode);
        $invoiceDiscount->totalpricevatamount = 0;
        $invoiceDiscount->unit = $this->bamboraHelper->_s("pcs.");
        $invoiceDiscount->vat =  0;

        $lines[] = $invoiceDiscount;

        //Add fee item
        if (isset($feeItem)) {
            $lines[] = $feeItem;
        }

        //Adjustment refund
        if ($creditMemo->getBaseAdjustment() > 0) {
            $invoiceAdjustment = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
            $invoiceAdjustment->description = $this->bamboraHelper->_s("Adjustment refund");
            $invoiceAdjustment->id = $this->bamboraHelper->_s("adjustment_refund");
            $invoiceAdjustment->linenumber = count($lines) + 1;
            $invoiceAdjustment->quantity = 1;
            $invoiceAdjustment->text = $this->bamboraHelper->_s("Adjustment refund");
            $invoiceAdjustment->totalprice = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getBaseAdjustment(), $minorunits, $roundingMode);
            $invoiceAdjustment->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($creditMemo->getBaseAdjustment(), $minorunits, $roundingMode);
            $invoiceAdjustment->totalpricevatamount = 0;
            $invoiceAdjustment->unit = $this->bamboraHelper->_s("pcs.");
            $invoiceAdjustment->vat =  0;

            $lines[] = $invoiceAdjustment;
        }

        return $lines;
    }

    /**
     * Filter an itemcollection and only return the visible items
     *
     * @param Mage_Sales_Model_Order_Creditmemo_Item[]|Mage_Sales_Model_Order_Invoice_Item[] $itemCollection
     * @return array
     */
    public function filterVisibleItemsOnly($itemCollection)
    {
        $items = array();
        foreach ($itemCollection as $orgItem) {
            $item = $orgItem->getOrderItem();
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $items[] =  $item;
            }
        }
        return $items;
    }

    /**
     * Get Invoice Lines
     *
     * @param Mage_Sales_Model_Order_Creditmemo_Item|Mage_Sales_Model_Order_Invoice_Item $item
     * @param Mage_Sales_Model_Order $order
     * @return Bambora_Online_Model_Api_Checkout_Request_Model_Line
     */
    public function createInvoiceLineFromInvoice($item, $order)
    {
        $minorunits = $this->bamboraHelper->getCurrencyMinorunits($order->getBaseCurrencyCode());
        $roundingMode = $this->getConfigData(BamboraConstant::ROUNDING_MODE);
        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Line */
        $invoiceLine = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
        $invoiceLine->description = $item->getName();
        $invoiceLine->id = $item->getSku();
        $invoiceLine->linenumber = array_search($item->getOrderItemId(), array_keys($order->getAllItems())) + 1;
        $invoiceLine->quantity = floatval($item->getQtyOrdered());
        $invoiceLine->text = $item->getName();
        $invoiceLine->totalprice = $this->bamboraHelper->convertPriceToMinorunits($item->getBasePrice(), $minorunits, $roundingMode);
        $invoiceLine->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorunits($item->getBasePriceInclTax(), $minorunits, $roundingMode);
        $invoiceLine->totalpricevatamount = $this->bamboraHelper->convertPriceToMinorunits($item->getBaseTaxAmount(), $minorunits, $roundingMode);
        $invoiceLine->unit = $this->bamboraHelper->_s("pcs.");
        $invoiceLine->vat = floatval($item->getTaxPercent());

        return $invoiceLine;
    }

    /**
     * Can do online action
     *
     * @param Varien_Object $payment
     * @return boolean
     */
    public function canOnlineAction($payment)
    {
        $storeId = $payment->getOrder()->getStoreId();
        if (intval($this->getConfigData(BamboraConstant::REMOTE_INTERFACE, $storeId)) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Can do action
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return boolean
     */
    public function canAction($payment)
    {
        $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
        if (!empty($transactionId)) {
            return true;
        }

        return false;
    }

    /**{@inheritDoc}*/
    public function canCapture()
    {
        $captureOrder = $this->_data["info_instance"]->getOrder();

        if ($this->_canCapture && $this->canAction($captureOrder->getPayment())) {
            return true;
        }

        return false;
    }

    /**{@inheritDoc}*/
    public function canRefund()
    {
        $creditOrder = $this->_data["info_instance"]->getOrder();

        if ($this->_canRefund && $this->canAction($creditOrder->getPayment())) {
            return true;
        }

        return false;
    }

    /**{@inheritDoc}*/
    public function canVoid(Varien_Object $payment)
    {
        $voidOrder = $payment->getOrder();
        if (!isset($voidOrder)) {
            $voidOrder = $this->_data["info_instance"]->getOrder();
        }
        if ($this->_canVoid && $this->canAction($voidOrder)) {
            return true;
        }

        return false;
    }

    /**
     * Get Bambora Checkout Accept url
     *
     * @return string
     */
    public function getAcceptUrl()
    {
        return Mage::getUrl('bambora/checkout/accept', array('_secure' => Mage::app()->getRequest()->isSecure()));
    }

    /**
     * Get Bambora Checkout Cancel url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return Mage::getUrl('bambora/checkout/cancel', array('_secure' => Mage::app()->getRequest()->isSecure()));
    }

    /**
     * Get Bambora Checkout Callback url
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return Mage::getUrl('bambora/checkout/callback', array('_secure' => Mage::app()->getRequest()->isSecure()));
    }

    /**
     * Get Admin message handler
     *
     * @return Mage_Core_Model_Abstract
     */
    public function adminMessageHandler()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Get Front message handler
     *
     * @return Mage_Core_Model_Abstract
     */
    public function frontMessageHandler()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Get Redirect Url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bambora/checkout/redirect', array('_secure' => Mage::app()->getRequest()->isSecure()));
    }

    /**
     * Get Chekout session
     *
     * @return Mage_Core_Model_Abstract
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get Quote
     *
     * @return mixed
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    /**
     * Get store
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * Get order
     *
     * @return boolean|Mage_Core_Model_Abstract
     */
    public function getOrder()
    {
        $session = $this->getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        return $order;
    }
}
