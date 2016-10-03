<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */
class Bambora_Bamboracheckout_Model_Bambora extends Mage_Payment_Model_Method_Abstract
{
    //
    //changing the payment to different from cc payment type and epay payment type
    //
    const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
    const PAYMENT_TYPE_SALE = 'SALE';

    protected $_code = 'bamboracheckout';
    protected $_formBlockType = 'bamboracheckout/checkout_form';
    protected $_infoBlockType = 'bamboracheckout/info_checkout';

    protected $_isGateway 				= true;
    protected $_canAuthorize 			= false; // NO! Authorization is not done by webservices! (PCI)
    protected $_canCapture 				= true;
    protected $_canCapturePartial 		= true;
    protected $_canRefund 				= true;
    protected $_canOrder 				= true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid 				= true;
    protected $_canUseInternal 			= true;	// If an internal order is created (phone / mail order) payment must be done using webpay and not an internal checkout method!
    protected $_canUseCheckout 			= true;
    protected $_canUseForMultishipping 	= true;
    protected $_canSaveCc 				= false; // NO CC is never saved. (PCI)

    protected $_order;

    private $bamboraCurrency;

    // Default constructor
    //
    public function __construct()
    {
        $this->bamboraCurrency = Mage::helper('bamboracheckout/bambora_currency');

    }

    public function getMerchantPaymentcards()
    {
        $bamboraMerchantProvider = Mage::getModel('bamboraproviders/merchant');
        $currencyCode = $this->getQuote()->getBaseCurrencyCode();

        $grandTotal= $this->getQuote()->getGrandTotal();
        $minorUnits = $this->bamboraCurrency->getCurrencyMinorUnits($currencyCode);
        $granTotalMinorUnits = $this->bamboraCurrency->convertPriceToMinorUnits($grandTotal, $minorUnits);

        $paymentcards = array();
        $getPaymentTypesResponce = $bamboraMerchantProvider->getPaymentTypes($currencyCode,$granTotalMinorUnits);
        $getPaymentTypesResponceJson = json_decode($getPaymentTypesResponce, true);

        if(!isset($getPaymentTypesResponceJson))
        {
            $errorMessage = new Exception("No response from Bambora backend");
            Mage::logException($errorMessage);
            return Mage::helper('bamboracheckout')->__("An error occured. Please contact the shop owner");
        }

        if (!$getPaymentTypesResponceJson['meta']['result'])
        {
            $errorMessage = new Exception("An error occured - ".$getPaymentTypesResponceJson['meta']['message']['merchant']);
            Mage::logException($errorMessage);
            return Mage::helper('bamboracheckout')->__("An error occured. Please contact the shop owner");
        }

        foreach($getPaymentTypesResponceJson['paymentcollections'] as $payment )
        {
            foreach($payment['paymentgroups'] as $card)
            {
                //enshure unique id:
                $cardname = $card['id'];
                $paymentcards[$cardname] = $card['id'];
            }
        }
        ksort($paymentcards);

        return $paymentcards;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('bamboracheckout/payment/redirect', array('_secure' => true));
    }

    public function getSession()
    {
        return Mage::getSingleton('bambora/session');
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


    public function createBamboraCheckoutRequest()
    {
        $order = $this->getOrder();
        $billing  = $order->getBillingAddress();
        $shipping = $order->getShippingAddress();
        if ($order->getBillingAddress()->getEmail()) {
            $email = $order->getBillingAddress()->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }

        $storeId = $order->getStoreId();
        $minorUnits = $this->bamboraCurrency->getCurrencyMinorUnits($this->getOrder()->getOrderCurrencyCode());
        $totalAmountMinorUnits = $this->bamboraCurrency->convertPriceToMinorUnits($this->getOrder()->getGrandTotal(), $minorUnits);

        $checkoutRequest = Mage::getModel('bamboramodels/checkoutrequest');

        $checkoutRequest->capturemulti = true;

        $bamboraCustomer = Mage::getModel('bamboramodels/customer');
        $bamboraCustomer->email = $email;
        $bamboraCustomer->phonenumber =$billing->getTelephone();
        $bamboraCustomer->phonenumbercountrycode = $billing->getCountryId();

        $checkoutRequest->customer = $bamboraCustomer;
        $checkoutRequest->instantcaptureamount = $this->getConfigData('instantcapture', $storeId) == 0 ? 0 : $totalAmountMinorUnits;
        $checkoutRequest->language = str_replace('_','-', Mage::app()->getLocale()->getLocaleCode());

        $bamboraBillingAddress = Mage::getModel('bamboramodels/address');

        $bamboraBillingAddress->att = "";
        $bamboraBillingAddress->city = $billing->getCity();
        $bamboraBillingAddress->country = $billing->getCountryModel()->getIso3Code();
        $bamboraBillingAddress->firstname = $billing->getFirstname();
        $bamboraBillingAddress->lastname = $billing->getLastname();
        $bamboraBillingAddress->street = $billing->getStreet(-1);
        $bamboraBillingAddress->zip = $billing->getPostcode();

        $bamboraOrder = Mage::getModel('bamboramodels/order');
        $bamboraOrder->billingaddress = $bamboraBillingAddress;
        $bamboraOrder->currency = $order->getOrderCurrencyCode();

        $bamboraOrderLines = array();
        $items = $order->getAllVisibleItems();
        $lineNumber = 1;
        foreach($items as $item)
        {
            $line = Mage::getModel('bamboramodels/orderline');
            $line->description = $item->getDescription() != null ? $item->getDescription() : $item->getName();
            $line->id = $item->getSku();
            $line->linenumber = $lineNumber;
            $line->quantity = floatval($item->getQtyOrdered());
            $line->text = $item->getName();
            $line->totalprice =  $this->bamboraCurrency->convertPriceToMinorUnits($item->getBaseRowTotal(),$minorUnits);
            $line->totalpriceinclvat = $this->bamboraCurrency->convertPriceToMinorUnits($item->getBaseRowTotalInclTax(),$minorUnits);
            $line->totalpricevatamount = $this->bamboraCurrency->convertPriceToMinorUnits($item->getBaseTaxAmount(),$minorUnits);
            $line->unit = Mage::helper('bamboracheckout')->__("pcs.");
            $line->vat = floatval($item->getTaxPercent());

            $bamboraOrderLines[] = $line;
            $lineNumber++;
        }


        //Add shipping as an orderline
        $shippingAmount = $order->getShippingAmount();
        if($shippingAmount > 0)
        {
            $shippingOrderline = Mage::getModel('bamboramodels/orderline');
            $shippingOrderline->description = Mage::helper('bamboracheckout')->__("shipping");
            $shippingOrderline->id = Mage::helper('bamboracheckout')->__("shipping");
            $shippingOrderline->linenumber = $lineNumber++;
            $shippingOrderline->quantity = 1;
            $shippingOrderline->text = Mage::helper('bamboracheckout')->__("shipping");
            $shippingTaxAmount =  $order->getShippingTaxAmount();
            $shippingAmountWithTax = $shippingAmount + $shippingTaxAmount;
            $shippingOrderline->totalprice = $this->bamboraCurrency->convertPriceToMinorUnits($shippingAmount, $minorUnits);
            $shippingOrderline->totalpriceinclvat = $this->bamboraCurrency->convertPriceToMinorUnits($shippingAmountWithTax, $minorUnits);
            $shippingOrderline->totalpricevatamount = $this->bamboraCurrency->convertPriceToMinorUnits($shippingTaxAmount, $minorUnits);

            $shippingOrderline->unit = Mage::helper('bamboracheckout')->__("pcs.");
            $shippingOrderline->vat = round( $shippingTaxAmount / $shippingAmount * 100);
            $bamboraOrderLines[] = $shippingOrderline;
        }


        $bamboraOrder->lines = $bamboraOrderLines;
        $bamboraOrder->ordernumber = $order->getIncrementId();

        $bamboraShippingAddress = Mage::getModel('bamboramodels/address');
        $bamboraShippingAddress->att = "";
        $bamboraShippingAddress->city = $shipping->getCity();
        $bamboraShippingAddress->country = $shipping->getCountryModel()->getIso3Code();
        $bamboraShippingAddress->firstname = $shipping->getFirstname();
        $bamboraShippingAddress->lastname = $shipping->getLastname();
        $bamboraShippingAddress->street = $shipping->getStreet(-1);
        $bamboraShippingAddress->zip = $shipping->getPostcode();
        $bamboraOrder->shippingaddress = $bamboraShippingAddress;

        $bamboraOrder->total = $this->bamboraCurrency->convertPriceToMinorUnits($order->getBaseTotalDue(),$minorUnits);
        $bamboraOrder->vatamount = $this->bamboraCurrency->convertPriceToMinorUnits($order->getBaseTaxAmount(),$minorUnits);

        $checkoutRequest->order = $bamboraOrder;

        $bamboraUrl = Mage::getModel('bamboramodels/url');
        $bamboraUrl->accept = Mage::getUrl('bamboracheckout/payment/success', array('_nosid' => true));
        $bamboraUrl->decline = Mage::getUrl('bamboracheckout/payment/cancel', array('_nosid' => true));
        $bamboraCallback = Mage::getModel('bamboramodels/callback');
        $bamboraCallback->url = Mage::getUrl('bamboracheckout/payment/callback', array('_nosid' => true));
        $bamboraUrl->callbacks = array();
        $bamboraUrl->callbacks[] = $bamboraCallback;
        $bamboraUrl->immediateredirecttoaccept = $this->getConfigData('immediateredirecttoaccept', $storeId);
        $checkoutRequest->url = $bamboraUrl;
        $paymentWindowId = $this->getConfigData('paymentwindowid', $storeId);
        $checkoutRequest->paymentwindowid = is_numeric($paymentWindowId) ? $paymentWindowId : 1;

        return $checkoutRequest;
    }

    public function setBamboraCheckout($setCheckoutRequest)
    {
        $checkoutProvider = Mage::getModel('bamboraproviders/checkout');
        $setBamboraCheckoutResponse = $checkoutProvider->setBamboraCheckout($setCheckoutRequest);
        $setCheckoutResponseJson = json_decode($setBamboraCheckoutResponse,true);

        if(!$setCheckoutResponseJson['meta']['result'])
        {
            Mage::getSingleton('core/session')->addError(Mage::helper('bamboracheckout')->__("An error occured. Please contact the shop owner"));
            $errorMessage = new Exception(Mage::helper('bamboracheckout')->__("An error occured - ").$setCheckoutResponseJson['meta']['message']['merchant']);
            Mage::log($errorMessage);
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('bamboracheckout/payment/cancel', array('_secure' => false)));
            Mage::app()->getResponse()->sendResponse();
            return;

        }

        return $setCheckoutResponseJson;
    }

    public function getBamboraPaymentWindowUrl()
    {
        $assetsProvider = Mage::getModel('bamboraproviders/assets');
        return $assetsProvider->getcheckoutpaymentwindowjs();

    }


}
?>