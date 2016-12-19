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
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';

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
                floatval($item->getTaxPercent()),
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
            null,
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
    public function createInvoiceLine($description, $id, $lineNumber, $quantity, $text, $totalPrice, $totalPriceVatAmount, $vat, $currencyCode, $discountAmount = 0)
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
        if(!isset($vat))
        {
            $vat = $totalPriceVatAmount > 0 && $totalPrice > 0  ? round($totalPriceVatAmount / $totalPrice * 100) : 0;
        }
        $line->vat = $vat;

        return $line;
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

    private function canOnlineAction($payment)
    {
        if (intval($this->getConfigData(BamboraConstant::REMOTE_INTERFACE, $payment->getOrder() ? $payment->getOrder()->getStoreId() : null)) === 1)
        {
            return true;
        }

        return false;
    }

    private function canAction($payment)
    {
		$transactionId = $payment->getAdditionalInformation($this::PSP_REFERENCE);
        if(!empty($transactionId))
		{
			return true;
		}

        return false;
    }

    public function canCapture()
	{
        $captureOrder = $this->_data["info_instance"]->getOrder();

		if($this->_canCapture && $this->canAction($captureOrder->getPayment()))
        {
            return true;
        }

        return false;
	}

    public function capture(Varien_Object $payment, $amount)
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
            Mage::throwException($this->bamboraHelper->_s("The capture action could not, be processed online. Please enable remote payment processing from the module configuration"));
        }

        try
        {
            /** @var Mage_Sales_Model_Order */
            $order = $payment->getOrder();

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->bamboraHelper->getCurrencyMinorunits($currency);

            /** @var Bambora_Online_Model_Api_Checkout_Request_Capture $captureRequest */
            $captureRequest = Mage::getModel(CheckoutApiModel::REQUEST_CAPTURE);
            $captureRequest->amount = $this->bamboraHelper->convertPriceToMinorUnits($amount, $minorunits);
            $captureRequest->currency = $currency;
            $captureRequest->invoicelines = $this->getCaptureInvoiceLines($order);

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
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

    public function canRefund()
    {
        $creditOrder = $this->_data["info_instance"]->getOrder();

		if($this->_canRefund && $this->canAction($creditOrder->getPayment()))
        {
            return true;
        }

		return false;
    }


    public function refund(Varien_Object $payment, $amount)
    {
        if(!$this->canOnlineAction($payment))
        {
            Mage::throwException($this->bamboraHelper->_s("The refund action could not, be processed online. Please enable remote payment processing from the module configuration"));
        }
        try
        {
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
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
        }

        return $this;
    }

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

    public function canVoid(Varien_Object $payment)
    {
        if($this->_canVoid && $this->canAction($payment))
        {
            return true;
        }

        return false;
    }


    public function void(Varien_Object $payment)
    {
        if(!$this->canOnlineAction($payment))
        {
            Mage::throwException($this->bamboraHelper->_s("The void action could not, be processed online. Please enable remote payment processing from the module configuration"));
        }
        try
        {
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
        }
        catch(Exception $e)
        {
            Mage::throwException($e->getMessage());
        }

        return $this;
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
        $invoiceItems = $invoice->getItemsCollection()->getItems();
        $lines = $this->getInvoiceLines($invoiceItems, $order);

        $shippingAmount = $invoice->getBaseShippingAmount();
        //Shipping discount handling
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
        $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines), 1, $shippingName, $shippingAmount, $invoice->getBaseShippingTaxAmount(), null, $invoice->getBaseCurrencyCode());

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
        $items = $creditMemo->getAllItems();
        foreach($items as $item)
        {

            if($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE)
            {
                $feeItem = $item;
                continue;
            }
            $lines[] = $item;
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
            $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines) + 1, 1, $shippingName, $shippingAmount, $creditMemo->getBaseShippingTaxAmount(),null, $creditMemo->getBaseCurrencyCode());
        }

        if(isset($feeItem))
        {
            $lines[] = $feeItem;
        }

        //Adjustment refund
        if($creditMemo->getBaseAdjustment() > 0)
        {
            $adjustmentRefundName = $this->bamboraHelper->_s("Adjustment refund");
            $lines[] = $this->createInvoiceLine($adjustmentRefundName, $adjustmentRefundName, count($lines) + 1, 1, $adjustmentRefundName, $creditMemo->getBaseAdjustment(), 0, null, $creditMemo->getBaseCurrencyCode());
        }
        return $lines;
    }

    public function getAcceptUrl()
    {
        return Mage::getUrl('bambora/checkout/accept', ['_secure' => Mage::app()->getRequest()->isSecure()]);
    }

    public function getCancelUrl()
    {
        return Mage::getUrl('bambora/checkout/cancel', ['_secure' => Mage::app()->getRequest()->isSecure()]);
    }

    public function getCallbackUrl()
    {
        return Mage::getUrl('bambora/checkout/callback', ['_secure' => Mage::app()->getRequest()->isSecure()]);
    }


    public function getCheckoutPaymentWindowUrl()
    {
        /** @var Bambora_Online_Model_Api_Checkout_Assets */
        $assetsApi = Mage::getModel(CheckoutApi::API_ASSETS);
        return $assetsApi->getCheckoutPaymentWindowJSUrl();
    }
    public function adminMessageHandler()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    public function frontMessageHandler()
    {
        return Mage::getSingleton('core/session');
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bambora/checkout/redirect', array('_secure' => Mage::app()->getRequest()->isSecure()));
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function getStore()
    {
        return Mage::app()->getStore();
    }

    public function getOrder()
    {
        $session = $this->getCheckout();
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        return $order;

    }
}