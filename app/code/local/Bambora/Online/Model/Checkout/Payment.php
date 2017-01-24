<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
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
	protected $_isGateway 				= true;
	protected $_canCapture 				= true;
	protected $_canCapturePartial 		= true;
    protected $_canRefund 				= true;
	protected $_canRefundInvoicePartial = true;
    protected $_canOrder 				= true;
    protected $_canVoid                 = true;
    public function canEdit(){return false;}

    /**
     * @var string
     */
    private $_apiKey;

    /**
     * @var Bambora_Online_Helper_Data
     */
    private $bamboraHelper;

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
        if(empty($this->_apiKey))
        {
            $storeId = isset($storeId) ? $this->getStore()->getId() : $storeId;

            $accesstoken = $this->getConfigData(BamboraConstant::ACCESS_TOKEN, $storeId);
            $merchantNumber = $this->getConfigData(BamboraConstant::MERCHANT_NUMBER, $storeId);
            $secrettokenCrypt = $this->getConfigData(BamboraConstant::SECRET_TOKEN, $storeId);
            $secrettoken = Mage::helper('core')->decrypt($secrettokenCrypt);

            $combined = $accesstoken . '@' . $merchantNumber .':'. $secrettoken;
            $encodedKey = base64_encode($combined);

            $this->_apiKey = 'Basic '.$encodedKey;
        }

        return $this->_apiKey;
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
        if(!isset($currency))
        {
            $currency = $this->getQuote()->getBaseCurrencyCode();
        }

        if(!isset($amount))
        {
            $amount = $this->getQuote()->getBaseGrandTotal();
        }

        $minorUnits = $this->bamboraHelper->getCurrencyMinorunits($currency);
        $amountMinorunits = $this->bamboraHelper->convertPriceToMinorUnits($amount, $minorUnits);

        /** @var Bambora_Online_Model_Api_Checkout_Merchant */
        $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);

        $paymentTypeResponse = $merchantApi->getPaymentTypes($currency, $amountMinorunits, $this->getApiKey());

        $message = "";
        if($this->bamboraHelper->validateCheckoutApiResult($paymentTypeResponse, $this->getQuote()->getId(), false, $message))
        {
            $paymentCardIdsArray = array();

            foreach($paymentTypeResponse->paymentCollections as $payment)
            {
                foreach($payment->paymentGroups as $group)
                {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
            return $paymentCardIdsArray;
        }
        else
        {
            $this->adminMessageHandler()->addError($message);
            return null;
        }
    }

    /**
     * Get Bambora Checkout payment window
     *
     * @return Bambora_Online_Model_Api_Checkout_Response_Checkout
     */
    public function getPaymentWindow()
    {
        $checkoutRequest = $this->createCheckoutRequest();

        /** @var Bambora_Online_Model_Api_Checkout_Checkout */
        $checkoutApi = Mage::getModel(CheckoutApi::API_CHECKOUT);
        $checkoutResponse = $checkoutApi->setCheckout($checkoutRequest, $this->getApiKey());

        $message = "";
        if(!$this->bamboraHelper->validateCheckoutApiResult($checkoutResponse, $checkoutRequest->order->ordernumber, false, $message))
        {
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
        if(!isset($order))
        {
            $order = $this->getOrder();
        }

        $billingAddress  = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if ($order->getBillingAddress()->getEmail())
        {
            $email = $order->getBillingAddress()->getEmail();
        }
        else
        {
            $email = $order->getCustomerEmail();
        }

        $storeId = $order->getStoreId();
        $minorUnits = $this->bamboraHelper->getCurrencyMinorUnits($order->getBaseCurrencyCode());
        $totalAmountMinorUnits = $this->bamboraHelper->convertPriceToMinorUnits($order->getBaseTotalDue(), $minorUnits);

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
        $bamboraOrder->vatamount = $this->bamboraHelper->convertPriceToMinorUnits($order->getBaseTaxAmount(), $minorUnits);

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

        if($billingAddress)
        {
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

        if($shippingAddress)
        {
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
        $items = $order->getAllVisibleItems();
        $lineNumber = 1;
        foreach($items as $item)
        {
            $bamboraOrderLines[] = $this->createInvoiceLine(
                $item->getDescription(),
                $item->getSku(),
                $lineNumber,
                floatval($item->getQtyOrdered()),
                $item->getName(),
                $item->getBaseRowTotal(),
                $item->getBaseTaxAmount(),
                $order->getBaseCurrencyCode(),
                $item->getBaseDiscountAmount());

            $lineNumber++;
        }

        //Add shipping line
        $bamboraOrderLines[] = $this->createInvoiceLine(
           $order->getShippingDescription(),
            $this->bamboraHelper->_s("Shipping"),
            $lineNumber++,
            1,
            $this->bamboraHelper->_s("Shipping"),
             $order->getBaseShippingAmount(),
            $order->getBaseShippingTaxAmount(),
            $order->getBaseCurrencyCode(),
            $order->getBaseShippingDiscountAmount());


        $bamboraOrder->lines = $bamboraOrderLines;
        $checkoutRequest->order = $bamboraOrder;

        return $checkoutRequest;
    }

    /**
     * Create Invoice Line
     *
     * @param mixed $description
     * @param mixed $id
     * @param mixed $lineNumber
     * @param mixed $quantity
     * @param mixed $text
     * @param mixed $totalPrice
     * @param mixed $totalPriceVatAmount
     * @param int|null $vat
     * @param mixed $currencyCode
     * @return Bambora_Online_Model_Api_Checkout_Request_Model_Line
     */
    public function createInvoiceLine($description, $id, $lineNumber, $quantity, $text, $totalPrice, $totalPriceVatAmount, $currencyCode, $discountAmount = 0)
    {
        $minorUnits = $this->bamboraHelper->getCurrencyMinorunits($currencyCode);

        /** @var Bambora_Online_Model_Api_Checkout_Request_Model_Line */
        $line = Mage::getModel(CheckoutApiModel::REQUEST_MODEL_LINE);
        $line->description = isset($description) ? $description : $text;
        $line->id = $id;
        $line->linenumber = $lineNumber;
        $line->quantity = $quantity;
        $line->text = $text;
        $line->totalprice = $this->bamboraHelper->convertPriceToMinorUnits(($totalPrice - $discountAmount), $minorUnits);
        $line->totalpriceinclvat = $this->bamboraHelper->convertPriceToMinorUnits((($totalPrice + $totalPriceVatAmount) - $discountAmount), $minorUnits);
        $line->totalpricevatamount = $this->bamboraHelper->convertPriceToMinorUnits($totalPriceVatAmount, $minorUnits);
        $line->unit = $this->bamboraHelper->_s("pcs.");

        //Calculate the percentage of tax
        $vat = $totalPriceVatAmount > 0 && $totalPrice > 0  ? round($totalPriceVatAmount / $totalPrice * 100) : 0;
        $line->vat = $vat;

        return $line;
    }

    /**{@inheritDoc}*/
    public function capture(Varien_Object $payment, $amount)
    {
        try
        {
            $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
            $isInstantCapure = $payment->getAdditionalInformation(BamboraConstant::INSTANT_CAPTURE);

            if($isInstantCapure === true)
            {
                $payment->setTransactionId($transactionId . '-' . BamboraConstant::INSTANT_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

                return $this;
            }

            if(!$this->canOnlineAction($payment))
            {
                throw new Exception($this->bamboraHelper->_s("The capture action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            /** @var Mage_Sales_Model_Order */
            $order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->bamboraHelper->getCurrencyMinorunits($currency);

            /** @var Bambora_Online_Model_Api_Checkout_Request_Capture $captureRequest */
            $captureRequest = Mage::getModel(CheckoutApiModel::REQUEST_CAPTURE);
            $captureRequest->amount = $this->bamboraHelper->convertPriceToMinorUnits($amount, $minorunits);
            $captureRequest->currency = $currency;

            //Only add invoice lines if it is a full capture
            $invoiceLines = null;
            if(floatval($amount) === floatval($order->getBaseTotalDue()))
            {
                $invoiceLines = $this->getCaptureInvoiceLines($order);
            }


            $captureRequest->invoicelines = $invoiceLines;

            /** @var Bambora_Online_Model_Api_Checkout_Transaction */
            $transactionApi = Mage::getModel(CheckoutApi::API_TRANSACTION);
            $captureResponse = $transactionApi->capture($transactionId, $captureRequest, $this->getApiKey());

            $message = "";
            if(!$this->bamboraHelper->validateCheckoutApiResult($captureResponse, $order->getIncrementId(),true, $message))
            {
                throw new Exception($this->bamboraHelper->_s('The capture action failed.') . ' - '.$message);
            }

            $transactionoperationId = "";
            foreach($captureResponse->transactionOperations as $transactionoperation)
            {
                $transactionoperationId = $transactionoperation->id;
            }

            $payment->setTransactionId($transactionoperationId . '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
            return null;
        }
    }

    /**{@inheritDoc}*/
    public function refund(Varien_Object $payment, $amount)
    {
        try
        {
            if(!$this->canOnlineAction($payment))
            {
                throw new Exception($this->bamboraHelper->_s("The refund action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
            $order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->bamboraHelper->getCurrencyMinorunits($currency);

            /** @var Bambora_Online_Model_Api_Checkout_Request_Credit */
            $creditRequest = Mage::getModel(CheckoutApiModel::REQUEST_CREDIT);
            $creditRequest->amount = $this->bamboraHelper->convertPriceToMinorUnits($amount, $minorunits);
            $creditRequest->currency = $currency;

            $creditMemo = $payment->getCreditmemo();
            $creditRequest->invoicelines = $this->getRefundInvoiceLines($creditMemo, $order);

            /** @var Bambora_Online_Model_Api_Checkout_Transaction */
            $transactionApi = Mage::getModel(CheckoutApi::API_TRANSACTION);
            $creditResponse = $transactionApi->credit($transactionId, $creditRequest, $this->getApiKey());
            $message = "";
            if(!$this->bamboraHelper->validateCheckoutApiResult($creditResponse, $order->getIncrementId(), true, $message))
            {
                throw new Exception($this->bamboraHelper->_s("The refund action failed.") . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach($creditResponse->transactionOperations as $transactionoperation)
            {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
            return null;
        }
    }

    /**{@inheritDoc}*/
    public function cancel(Varien_Object $payment)
    {
        try
        {
            $this->void($payment);
            $this->adminMessageHandler()->addSuccess($this->bamboraHelper->_s("The payment have been voided").' ('.$payment->getOrder()->getIncrementId() .')');
        }
        catch(Exception $e)
        {
            $this->adminMessageHandler()->addError($e->getMessage());
        }

        return $this;
    }

    /**{@inheritDoc}*/
    public function void(Varien_Object $payment)
    {
        try
        {
            if(!$this->canOnlineAction($payment))
            {
                throw new Exception($this->bamboraHelper->_s("The void action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
            $order = $payment->getOrder();

            /** @var Bambora_Online_Model_Api_Checkout_Transaction */
            $transactionApi = Mage::getModel(CheckoutApi::API_TRANSACTION);
            $deleteResponse = $transactionApi->delete($transactionId,$this->getApiKey());
            $message = "";
            if(!$this->bamboraHelper->validateCheckoutApiResult($deleteResponse, $order->getIncrementId(), true, $message))
            {
                throw new Exception($this->bamboraHelper->_s("The void action failed.") . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach($deleteResponse->transactionOperations as $transactionoperation)
            {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
            return null;
        }
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param string $transactionId
     * @param mixed $orderId
     * @param bool $showError
     * @return Bambora_Online_Model_Api_Checkout_Response_Transaction|null
     * @throws Exception
     */
    public function getTransaction($transactionId, $orderId)
    {
        $transaction = null;
        try
        {
            /** @var Bambora_Online_Model_Api_Checkout_Merchant */
            $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);
            $transactionResponse = $merchantApi->getTransaction($transactionId, $this->getApiKey());

            $message = "";
            if(!$this->bamboraHelper->validateCheckoutApiResult($transactionResponse, $orderId, true, $message))
            {
                throw new Exception($this->bamboraHelper->_s("The Get Transaction action failed.") . ' - '.$message);
            }
            $transaction = $transactionResponse->transaction;
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
        }

        return $transaction;
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param string $transactionId
     * @param mixed $orderId
     * @param bool $showError
     * @return Bambora_Online_Model_Api_Checkout_Response_Transaction|null
     * @throws Exception
     */
    public function getTransactionOperations($transactionId, $orderId)
    {
        $transactionOperations = null;
        try
        {
            /** @var Bambora_Online_Model_Api_Checkout_Merchant */
            $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);
            $listTransactionOperationsResponse = $merchantApi->getTransactionOperations($transactionId, $this->getApiKey());

            $message = "";
            if(!$this->bamboraHelper->validateCheckoutApiResult($listTransactionOperationsResponse, $orderId, true, $message))
            {
                throw new Exception($this->bamboraHelper->_s("The List TransactionOperations action failed.") . ' - '.$message);
            }
            $transactionOperations = $listTransactionOperationsResponse->transactionOperations;
        }
        catch(Exception $e)
        {
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
    private function getCaptureInvoiceLines($order)
    {
        $invoice = $order->getInvoiceCollection()->getLastItem();
        $invoiceItems = $order->getAllVisibleItems();
        $lines = array();
        $feeItem = null;
        foreach($invoiceItems as $item)
        {
            if($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE)
            {
                $feeItem = $this->createInvoiceLineFromInvoice($item, $order);
                continue;
            }
            $lines[] = $this->createInvoiceLineFromInvoice($item, $order);
        }

        //Shipping discount handling
        $shippingAmount = $invoice->getBaseShippingAmount();
        if($order->getBaseShippingDiscountAmount() > 0)
        {
            $invoiceShipmentAmount = $invoice->getBaseShippingAmount();
            $shipmentDiscount = $order->getBaseShippingDiscountAmount();

            if(($invoiceShipmentAmount - $shipmentDiscount) < 0)
            {
                $shippingAmount = 0;
            }
            else
            {
                $shippingAmount = $invoiceShipmentAmount - $shipmentDiscount;
            }
        }

        //Shipping
        $shippingName = $this->bamboraHelper->_s("Shipping");
        $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines), 1, $shippingName, $shippingAmount, $invoice->getBaseShippingTaxAmount(), $invoice->getBaseCurrencyCode());

        //Add fee item
        if(isset($feeItem))
        {
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

        foreach($items as $item)
        {
            if($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE)
            {
                $feeItem = $this->createInvoiceLineFromInvoice($item, $order);
                continue;
            }
            $lines[] = $this->createInvoiceLineFromInvoice($item, $order);
        }

        $shippingAmount = $creditMemo->getBaseShippingAmount();
        //Shipping discount handling
        if($order->getBaseShippingDiscountAmount() > 0)
        {
            $creditShipmentAmount = $creditMemo->getBaseShippingAmount();
            $shipmentDiscount = $order->getBaseShippingDiscountAmount();

            if(($creditShipmentAmount - $shipmentDiscount) < 0)
            {
                $shippingAmount = 0;
            }
            else
            {
                $shippingAmount = $creditShipmentAmount - $shipmentDiscount;
            }
        }

        //Shipping
        if($shippingAmount > 0)
        {
            $shippingName = $this->bamboraHelper->_s("Shipping");
            $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines) + 1, 1, $shippingName, $shippingAmount, $creditMemo->getBaseShippingTaxAmount(), $creditMemo->getBaseCurrencyCode());
        }

        //Add fee item
        if(isset($feeItem))
        {
            $lines[] = $feeItem;
        }

        //Adjustment refund
        if($creditMemo->getBaseAdjustment() > 0)
        {
            $adjustmentRefundName = $this->bamboraHelper->_s("Adjustment refund");
            $lines[] = $this->createInvoiceLine($adjustmentRefundName, $adjustmentRefundName, count($lines) + 1, 1, $adjustmentRefundName, $creditMemo->getBaseAdjustment(), 0, $creditMemo->getBaseCurrencyCode());
        }
        return $lines;
    }

    /**
     * Filter an itemcollection and only return the visible items
     *
     * @param Mage_Sales_Model_Order_Creditmemo_Item[]|Mage_Sales_Model_Order_Invoice_Item[] $itemCollection
     * @return array
     */
    private function filterVisibleItemsOnly($itemCollection)
    {
        $items = array();
        foreach ($itemCollection as $orgItem)
        {
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
    private function createInvoiceLineFromInvoice($item, $order)
    {
        $invoiceLine = $this->createInvoiceLine(
            $item->getDescription(),
            $item->getSku(),
            array_search($item->getOrderItemId(), array_keys($order->getAllItems())) + 1,
            floatval($item->getQty()),
            $item->getName(),
            $item->getBaseRowTotal(),
            $item->getBaseTaxAmount(),
            $order->getBaseCurrencyCode(),
            $item->getBaseDiscountAmount());

        return $invoiceLine;
    }

    /**
     * Can do online action
     *
     * @param Varien_Object $payment
     * @return boolean
     */
    private function canOnlineAction($payment)
    {
        $storeId = $payment->getOrder()->getStoreId();
        if (intval($this->getConfigData(BamboraConstant::REMOTE_INTERFACE, $storeId)) === 1)
        {
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
    private function canAction($payment)
    {
		$transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
        if(!empty($transactionId))
		{
			return true;
		}

        return false;
    }

    /**{@inheritDoc}*/
    public function canCapture()
	{
        $captureOrder = $this->_data["info_instance"]->getOrder();

		if($this->_canCapture && $this->canAction($captureOrder->getPayment()))
        {
            return true;
        }

        return false;
	}

    /**{@inheritDoc}*/
    public function canRefund()
    {
        $creditOrder = $this->_data["info_instance"]->getOrder();

		if($this->_canRefund && $this->canAction($creditOrder->getPayment()))
        {
            return true;
        }

		return false;
    }

    /**{@inheritDoc}*/
    public function canVoid(Varien_Object $payment)
    {
        $voidOrder = $payment->getOrder();
        if(!isset($voidOrder))
        {
            $voidOrder = $this->_data["info_instance"]->getOrder();
        }
        if($this->_canVoid && $this->canAction($voidOrder))
        {
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
        return Mage::getUrl('bambora/checkout/accept', ['_secure' => Mage::app()->getRequest()->isSecure()]);
    }

    /**
     * Get Bambora Checkout Cancel url
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return Mage::getUrl('bambora/checkout/cancel', ['_secure' => Mage::app()->getRequest()->isSecure()]);
    }

    /**
     * Get Bambora Checkout Callback url
     *
     * @return string
     */
    public function getCallbackUrl()
    {
        return Mage::getUrl('bambora/checkout/callback', ['_secure' => Mage::app()->getRequest()->isSecure()]);
    }

    /**
     * Get Bambora Checkout Payment window url
     *
     * @return string
     */
    public function getCheckoutPaymentWindowUrl()
    {
        /** @var Bambora_Online_Model_Api_Checkout_Assets */
        $assetsApi = Mage::getModel(CheckoutApi::API_ASSETS);
        return $assetsApi->getCheckoutPaymentWindowJSUrl();
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