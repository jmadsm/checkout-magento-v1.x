<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_PaymentController extends Mage_Core_Controller_Front_Action
{
    protected $_order = null;

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1', '403 Session Expired');
            exit;
        }
    }


    public function redirectAction()
    {      
        $session = Mage::getSingleton('checkout/session');

        $this->_order = Mage::getModel('sales/order');
        $this->_order->loadByIncrementId($session->getLastRealOrderId());
        
        //Fix for if the custommer click on the back button.
        if($this->_order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING)
        {
            $this->_redirect('checkout/cart');
            return;
        }

        $this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
            Mage::helper("bamboracheckout")->__("The Order is placed using Bambora Checkout, and is now awaiting payment.")
         );
        $this->_order->save();
        
        $session->setBamboraCheckoutBamboraQuoteId($session->getQuoteId());
        $session->unsQuoteId();
        $session->unsRedirectUrl();
          
        $this->loadLayout();
        $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('bamboracheckout/checkout_redirect'));
        $this->renderLayout();
    }

    public function successAction()
    {       
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getBamboraCheckoutBamboraQuoteId(true));
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }
    

    public function callbackAction()
    {
        if (!isset($_GET["txnid"]))
        {
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setHeader("Description","Argument not found",true); 
            return $this->_response;
        }
        
        
        $bamboraMerchantProvider = Mage::getModel("bamboraproviders/merchantprovider");

        $transactionInfomation = $bamboraMerchantProvider->gettransactionInformation($_GET["txnid"]);

        $transactionInfomationJson = json_decode($transactionInfomation,true);

        if(!$transactionInfomationJson["meta"]["result"])
        {
            $errorMessage = new Exception(Mage::helper("bamboracheckout")->__("An error occured - ").$transactionInfomationJson["meta"]["message"]["merchant"]); 
            Mage::log($errorMessage, Zend_Log::ERR);
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setHeader("Description",$transactionInfomationJson["meta"]["message"]["merchant"],true);  
            return $this->_response;
        }

        $bambora = Mage::getModel('bamboracheckout/bambora');

        $orderId = $transactionInfomationJson["transaction"]["orderid"];
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if(isset($order) && $order->hasData())
        {
            $errorMessage = new Exception(Mage::helper("bamboracheckout")->__("An error occured - ").$transactionInfomationJson["meta"]["message"]["merchant"]); 
            Mage::log($errorMessage, Zend_Log::ERR);
            $this->getResponse()->setHttpResponseCode(400);
            $this->getResponse()->setHeader("Description",$transactionInfomationJson["meta"]["message"]["merchant"],true);  
            
            return $this->_response;
        }
        //If order status is processing return 200 OK.
        if($this->_order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING)
        {
            $this->getResponse()->setHttpResponseCode(200);
            $this->getResponse()->setHeader("Description","Already Processing",true);
            return $this->_response;
        }
        
        // validate md5 if enabled
        if ((strlen($bambora->getConfigData('md5key', $this->_order->getStoreId()))) > 0)
		{
			$accept_params = $_GET;
			$var = "";
			foreach ($accept_params as $key => $value)
			{
				if($key != "hash")
					$var .= $value;
			}
            
            if (md5($var . $bambora->getConfigData('md5key', $this->_order->getStoreId())) != $_GET["hash"]) 
			{
                $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage::helper("bamboracheckout")->__("Callback failed md5 check"));
                $this->_order->save();
                $this->getResponse()->setHttpResponseCode(400);
                $this->getResponse()->setHeader("Description","Failed md5 validation",true);  
                return $this->_response;
            }
        }

        $this->_authOrder($this->_order);
        
        $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, Mage_Sales_Model_Order::STATE_PROCESSING,
           Mage::helper("bamboracheckout")->__("Payment succesfull - Bambora Transaction Id").": ".$_GET["txnid"]);

        $this->_order->save();

        //
        // Add the transaction fee to the shipping and handling amount
        //
        if (isset($_GET['txnfee']) && strlen($_GET['txnfee']) > 0) 
        {
            if (((int)$bambora->getConfigData('addfeetoshipping', $this->_order->getStoreId())) == 1)
            {
                $bamboraCurrency = Mage::helper("bamboracheckout/bambora_currency");
                $minorUnits = $bamboraCurrency->getCurrencyMinorunits($_GET["currency"]);
                
                $tnxfee = $bamboraCurrency->convertPriceFromMinorUnits($_GET['txnfee'],$minorUnits);

                $this->_order->setBaseShippingAmount($this->_order->getBaseShippingAmount() + $tnxfee);
                $this->_order->setBaseGrandTotal($this->_order->getBaseGrandTotal() + $tnxfee);
                
                $storefee = Mage::helper('directory')->currencyConvert($tnxfee, $this->_order->getBaseCurrencyCode(), $this->_order->getOrderCurrencyCode());
                
                $this->_order->setShippingAmount($this->_order->getShippingAmount() + $storefee);
                $this->_order->setGrandTotal($this->_order->getGrandTotal() + $storefee);
                
                $this->_order->save();
            }
        }

        //
        // Send email order confirmation (if enabled). May be done only once!
        //        	
        if ($bambora->getConfigData('sendmailorderconfirmation', $this->_order->getStoreId()) == 1)
        {
            $this->_order->sendNewOrderEmail();
            $this->_order->save();
        }

        //
        // Create an invoice if the the setting instantinvoice is set to Yes
        //
        if($bambora->getConfigData('instantinvoice', $this->_order->getStoreId()) == 1)
        {
            if($this->_order->canInvoice())
            {
                $invoice = $this->_order->prepareInvoice();
                
                //Already captured by instantcapture
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder())->save();
                
                if($bambora->getConfigData('instantinvoicemail', $this->_order->getStoreId()) == 1)
                {
                    $invoice->setEmailSent(true);
                    $invoice->save();
                    $invoice->sendEmail();
                }
            }
        }
        return $this->_response;
    }

    protected function _authOrder($order)
    {
        $payment = $order->getPayment();
        $this->_fillPaymentByResponse($payment);

        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
		
        $order->save();
    }

    protected function _fillPaymentByResponse(Varien_Object $payment)
    {
        $payment->setTransactionId($_GET["txnid"])
            ->setParentTransactionId(null)
            ->setIsTransactionClosed(0)
            ->setTransactionAdditionalInfo("Transaction ID", $_GET["txnid"]);
    }
    
    /**
     * When a customer cancel payment from bambora.
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getBamboraCheckoutQuoteId(true));
		
		$lastQuoteId = $session->getLastQuoteId();
	    $lastOrderId = $session->getLastOrderId();
		
		if($lastQuoteId && $lastOrderId)
		{
			$order = Mage::getModel('sales/order')->load($lastOrderId);
			if($order->canCancel())
			{
				$quote = Mage::getModel('sales/quote')->load($lastQuoteId);
				$quote->setIsActive(true)->save();
				$order->cancel();
				$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED,
                    Mage::helper("bamboracheckout")->__("Payment canceled from Bambora Checkout"));
                $order->setStatus('canceled');
                $order->save();
				
                Mage::getSingleton('core/session')->setFailureMsg('order_failed');
				Mage::getSingleton('checkout/session')->setFirstTimeChk('0');
			}
		}
		
        $this->_redirect('checkout/cart');
        return;
    }
}

?>