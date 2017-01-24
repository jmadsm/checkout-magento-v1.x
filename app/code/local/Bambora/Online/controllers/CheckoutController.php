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
        $session->setBamboraCheckoutBamboraQuoteId($session->getQuoteId());

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($session->getLastRealOrderId());
        $payment = $order->getPayment();

        $pspReference = $payment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);

        if(!empty($pspReference) || empty($session->getLastSuccessQuoteId()))
        {
            $this->_redirect('checkout/onepage/success');
            return;
        }

        $paymentMethod = $this->getMethodInstance($order);
        $paymentWindow = $paymentMethod->getPaymentWindow();
        if(!isset($paymentWindow))
        {
            $this->_redirect($paymentMethod->getCancelUrl());
            return;
        }

        //If payment window is set to full screen
        if(intval($paymentMethod->getConfigData(BamboraConstant::WINDOW_STATE, $paymentMethod->getStore()->getStoreId())) === 1)
        {
            Mage::app()->getFrontController()->getResponse()->setRedirect($paymentWindow->url);
            Mage::app()->getResponse()->sendResponse();
            return;
        }

        $paymentData = array("paymentWindowUrl"=> $paymentMethod->getCheckoutPaymentWindowUrl(),
                                 "bamboraCheckoutUrl"=> $paymentWindow->url,
                                 "cancelUrl"=> $paymentMethod->getCancelUrl(),
                                 "headerText"=> $this->bamboraHelper->_s("Thank you for using Bambora Checkout"),
                                 "headerText2"=> $this->bamboraHelper->_s("Please wait..."));

        $this->loadLayout();
        $block = $this->getLayout()->createBlock('bambora/checkout_redirect', 'bamboraredirect', $paymentData);
        $this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
    }


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
		if ($order->getId())
		{
			$session->getQuote()->setIsActive(0)->save();
	        $session->clear();
    	    try
			{
				$order->setActionFlag(Mage_Sales_Model_Order::ACTION_FLAG_CANCEL, true);
            	$order->cancel()->save();
        	}
			catch (Mage_Core_Exception $e)
			{
            	Mage::logException($e);
        	}
			$items = $order->getItemsCollection();
        	foreach ($items as $item)
			{
				try
				{
					$cart->addOrderItem($item);
            	}
				catch (Mage_Core_Exception $e)
				{
					$session->addError($this->__($e->getMessage()));
                	Mage::logException($e);
                	continue;
            	}
        	}
        	$cart->save();
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
        if($this->validateCallback($message, $transactionResponse, $order))
        {
            $message = $this->processCallback($transactionResponse, $responseCode);
        }
        else
        {
            if(isset($order))
            {
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
     * @param string &$message
     * @return boolean
     */
    private function validateCallback(&$message, &$transactionResponse, &$order)
    {
        if(!isset($_GET["txnid"]))
        {
            $message = "No GET(txnid) was supplied to the system!";
		    return false;
        }

        if (!isset($_GET["orderid"]))
	    {
            $message = "No GET(orderid) was supplied to the system!";
		    return false;
		}

        if (!isset($_GET["amount"])) {
            $message = "No GET(amount) supplied to the system!";
            return false;
        }

        if (!isset($_GET["currency"])) {
            $message = "No GET(currency) supplied to the system!";
            return false;
        }
        /** @var Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($_GET["orderid"]);
        if(!isset($order) || !$order->getId())
        {
			$message = "The order object could not be loaded";
			return false;
        }
        if($order->getIncrementId() != $_GET["orderid"])
        {
            $message = "The loaded order id does not match the callback GET(orderId)";
			return false;
        }
        if($this->getMethod($order) !== Bambora_Online_Model_Checkout_Payment::METHOD_CODE)
        {
            $message = "The Payment method of the order dont match. Order method: ". $order->getPayment()->getMethod();
			return false;
        }


        $method = $this->getMethodInstance($order);
        $storeId = $order->getStoreId();
        $storeMd5 = $method->getConfigData(BamboraConstant::MD5_KEY, $storeId);
        if (!empty($storeMd5))
		{
			$accept_params = $_GET;
			$var = "";
			foreach ($accept_params as $key => $value)
			{
				if($key != "hash")
                {
					$var .= $value;
                }
			}

            $storeHash = md5($var . $storeMd5);
            if ($storeHash != $_GET["hash"])
			{
				$message = "Hash validation failed - Please check your MD5 key";
				return false;
            }
        }

        //Validate Transaction
        $transactionId = $_GET['txnid'];
        $apiKey = $method->getApiKey($order->getStoreId());

        /** @var Bambora_Online_Model_Api_Checkout_Merchant */
        $merchantApi = Mage::getModel(CheckoutApi::API_MERCHANT);

        $transactionResponse = $merchantApi->getTransaction($transactionId,$apiKey);

        //Validate transaction
        $meassage = "";
        if(!$this->bamboraHelper->validateCheckoutApiResult($transactionResponse, $transactionId, true, $meassage))
        {
            return false;
        }

        return true;
    }


    /**
     * Process the callback
     *
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transactionResponse
     * @param string &$responseCode
     */
    private function processCallback($transactionResponse, &$responseCode)
    {
        $message = '';
        $order = Mage::getModel('sales/order')->loadByIncrementId($_GET["orderid"]);
        $payment = $order->getPayment();
        try
        {
            $pspReference = $payment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
            if(empty($pspReference) && !$order->isCanceled())
            {
                $method = $this->getMethodInstance($order);
                $storeId = $order->getStoreId();

                $this->updatePaymentData($order, $method->getConfigData(BamboraConstant::ORDER_STATUS_AFTER_PAYMENT, $storeId), $transactionResponse);

                if (intval($method->getConfigData(BamboraConstant::ADD_SURCHARGE_TO_PAYMENT, $storeId)) == 1 && isset($_GET['txnfee']) && floatval($_GET['txnfee']) > 0)
                {
                    $this->addSurchargeItemToOrder($order, $transactionResponse);
                }

                if (intval($method->getConfigData(BamboraConstant::SEND_MAIL_ORDER_CONFIRMATION, $storeId) == 1))
                {
                    $this->sendOrderEmail($order);
                }

                if(intval($method->getConfigData(BamboraConstant::INSTANT_INVOICE, $storeId)) == 1)
                {
                    if(intval($method->getConfigData(BamboraConstant::REMOTE_INTERFACE, $storeId)) == 1 || intval($method->getConfigData(BamboraConstant::INSTANT_CAPTURE, $storeId)) === 1)
                    {
                        $this->createInvoice($order);
                    }
                    else
                    {
                        $order->addStatusHistoryComment($this->bamboraHelper->_s("Could not use instant invoice."). ' - '. $this->bamboraHelper->_s("Please enable remote payment processing from the module configuration"));
                        $order->save();
                    }
                }
                $message = "Callback Success - Order created";
            }
            else
            {
                if($order->isCanceled())
                {
                    $message = "Callback Success - Order was canceled by Magento";
                }
                else
                {
                    $message = "Callback Success - Order already created";
                }
            }
            $responseCode = '200';

        }
        catch(Exception $e)
        {
            $payment->setAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE, "");
            $payment->save();
            $responseCode = '500';
            $message = "Callback Failed: " .$e->getMessage();
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
        $payment = $order->getPayment();
        $txnId = $transactionResponse->transaction->id;
        $payment->setTransactionId($txnId);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE, $txnId);
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        $payment->setCcType($transactionResponse->transaction->information->paymentTypes[0]->displayName);
        $payment->setCcNumberEnc($transactionResponse->transaction->information->primaryAccountnumbers[0]->number);

        $methodInstance = $this->getMethodInstance($order);
        $isInstantCapture = intval($methodInstance->getConfigData(BamboraConstant::INSTANT_CAPTURE, $order->getStoreId())) === 1 ? true : false;

        $payment->setAdditionalInformation(BamboraConstant::INSTANT_CAPTURE, $isInstantCapture);

        $payment->save();

        $message = $this->bamboraHelper->_s("Payment authorization was a success.") . ' ' . $this->bamboraHelper->_s("Transaction ID").': '.$txnId;
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatusAfterPayment, $message, false);
        $order->save();
    }


    /**
     * Add Surcharge item to the order as a order line
     *
     * @param Mage_Sales_Model_Order $order
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transactionResponse
     * @return void
     */
    private function addSurchargeItemToOrder($order, $transactionResponse)
    {
        $baseFeeAmount = floatval($this->bamboraHelper->convertPriceFromMinorUnits($transactionResponse->transaction->total->feeamount, $transactionResponse->transaction->currency->minorunits));
        $feeAmount = Mage::helper('directory')->currencyConvert($baseFeeAmount, $order->getBaseCurrencyCode(), $order->getOrderCurrencyCode());

        foreach($order->getAllItems() as $item)
        {
            if($item->getSku() === BamboraConstant::BAMBORA_SURCHARGE)
            {
                return;
            }
        }

        /** @var Mage_Sales_Model_Order_Item */
        $feeItem = Mage::getModel('sales/order_item');

        $feeItem->setSku(BamboraConstant::BAMBORA_SURCHARGE);
        $text = $transactionResponse->transaction->information->paymentTypes[0]->displayName . ' - ' . $this->bamboraHelper->_s('Surcharge fee');
        $feeItem->setName($text);
        $feeItem->setBaseCost($baseFeeAmount);
        $feeItem->setBasePrice($baseFeeAmount);
        $feeItem->setBasePriceInclTax($baseFeeAmount);
        $feeItem->setBaseOriginalPrice($baseFeeAmount);
        $feeItem->setBaseRowTotal($baseFeeAmount);
        $feeItem->setBaseRowTotalInclTax($baseFeeAmount);

        $feeItem->setCost($feeAmount);
        $feeItem->setPrice($feeAmount);
        $feeItem->setPriceInclTax($feeAmount);
        $feeItem->setOriginalPrice($feeAmount);
        $feeItem->setRowTotal($feeAmount);
        $feeItem->setRowTotalInclTax($feeAmount);

        $feeItem->setProductType(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL);
        $feeItem->setIsVirtual(1);
        $feeItem->setQtyOrdered(1);
        $feeItem->setStoreId($order->getStoreId());
        $feeItem->setOrderId($order->getId());

        $order->addItem($feeItem);

        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $baseFeeAmount);
        $order->setBaseSubtotal($order->getBaseSubtotal() + $baseFeeAmount);
        $order->setGrandTotal($order->getGrandTotal() + $feeAmount);
        $order->setSubtotal($order->getSubtotal() + $feeAmount);


        $feeMessage = $text . ' ' .$this->bamboraHelper->_s("added to order");
        $order->addStatusHistoryComment($feeMessage);
        $order->save();
    }

    /**
     * Send an order confirmation to the customer
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function sendOrderEmail($order)
    {
        $order->sendNewOrderEmail();
        $order->setIsCustomerNotified(1);
        $order->addStatusHistoryComment(sprintf($this->bamboraHelper->_s("Notified customer about order #%s"), $order->getIncrementId()))
            ->setIsCustomerNotified(true);
        $order->save();
    }

    /**
     * Create an invoice
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function createInvoice($order)
    {
        if($order->canInvoice())
        {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->save();

            $transactionSave = Mage::getModel('core/resource_transaction')
              ->addObject($invoice)
              ->addObject($invoice->getOrder());
            $transactionSave->save();

            $method = $this->getMethodInstance($order);
            if(intval($method->getConfigData(BamboraConstant::INSTANT_INVOICE_MAIL, $order->getStoreId())) == 1)
            {
                $invoice->sendEmail();
                $order->addStatusHistoryComment(sprintf($this->bamboraHelper->_s("Notified customer about invoice #%s"), $invoice->getId()))
                    ->setIsCustomerNotified(true);
                $order->save();
            }
        }
    }
}