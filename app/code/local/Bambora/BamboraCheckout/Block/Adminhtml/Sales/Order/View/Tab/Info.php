<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
		return parent::getOrder();
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return parent::getOrder();
    }

    /**
     * Retrieve order totals block settings
     *
     * @return array
     */
    public function getOrderTotalData()
    {
        return parent::getOrderTotalData();
    }

    public function getOrderInfoData()
    {
        return parent::getOrderInfoData();
    }

    public function getTrackingHtml()
    {
        return parent::getTrackingHtml();
    }

    public function getItemsHtml()
    {
        return $this->getChildHtml('order_items');
    }

    /**
     * Retrive giftmessage block html
     *
     * @return string
     */
    public function getGiftmessageHtml()
    {
        return parent::getGiftmessageHtml();
    }
    
    private function checkBamboraModule()
    {
        $modules = Mage::getConfig()->getNode('modules')->children();
        $modulesArray = (array)$modules;
        $bamboraModule = $modulesArray['Bambora_BamboraCheckout'];

        return isset($bamboraModule) && $bamboraModule->is('active');
    }
    
    

    public function getPaymentHtml()
    {
        $res = $this->getChildHtml('order_payment');
        //Check for Bambora Module
        if(!$this->checkBamboraModule())
        {
            return $res;
        }
        
        
        $payment = $this->getOrder()->getPayment();
        
        $transactionId = $payment->getAuthorizationTransaction()!= null ? $payment->getAuthorizationTransaction()->getTxnId() : $payment->getLastTransId();
        if(!isset($transactionId))
        {
            return $res;
        }    
        
        $merchantProvider = Mage::getModel("bamboraproviders/merchant");

        $bamboraTransaction = $merchantProvider->gettransactionInformation($transactionId);
        $bamboraTransactionsJson = json_decode($bamboraTransaction, true);
        
        if (!isset($bamboraTransactionsJson) || !$bamboraTransactionsJson["meta"]["result"]) 
        {   
            return $res;
        }

        $bamboraCurrency = Mage::helper("bamboracheckout/bambora_currency");
        $currency = $bamboraTransactionsJson["transaction"]["currency"]["code"];
        $minorUnits = $bamboraCurrency->getCurrencyMinorunits($currency);
        
        // Payment has been made to this order
        $res = '<table class="bambora_paymentinfo" border="0" width="100%">';
        $res .= '<tr><td colspan="2"><p class="bambora_title">'. Mage::helper('bamboracheckout')->__("Bambora Checkout") . '</p></div></td></tr>';
        //Transaction ID
        $res .= "<tr><td width='150'>" . Mage::helper('bamboracheckout')->__('Transaction ID:') . "</td>";
        $res .= "<td>" . $transactionId. "</td></tr>";
        
        //Amount
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__('Amount:') . "</td>";
        $res .= "<td>".Mage::helper('core')->currency($bamboraCurrency->convertPriceFromMinorUnits($bamboraTransactionsJson["transaction"]["total"]["authorized"],$minorUnits),true,false). "</td></tr>";

        //Date
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__("Transaction date:") . "</td>";
        $res .= "<td>" .Mage::helper('core')->formatDate($bamboraTransactionsJson["transaction"]["createddate"], 'medium', false) . "</td></tr>";

        //Card Type with logo
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__("Card type:") . "</td>";
        $res .= "<td>".$bamboraTransactionsJson["transaction"]["information"]["paymenttypes"][0]["displayname"];
        $res .= $this->printLogo($bamboraTransactionsJson["transaction"]["information"]["paymenttypes"][0]["groupid"])."</td></tr>";


        //Truncated Cardnumber
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__("Card number:") . "</td>";
        $res .= "<td>" . $bamboraTransactionsJson["transaction"]["information"]["primaryaccountnumbers"][0]["number"] . "</td></tr>";
        
        //Transaction Fee amount
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__("Transaction fee:") . "</td>";
        $res .= "<td>" .Mage::helper('core')->currency($bamboraCurrency->convertPriceFromMinorUnits($bamboraTransactionsJson["transaction"]["total"]["feeamount"],$minorUnits),true,false). "</td></tr>";

        //Total Captured
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__("Captured:") . "</td>";
        $res .= "<td>".Mage::helper('core')->currency($bamboraCurrency->convertPriceFromMinorUnits($bamboraTransactionsJson["transaction"]["total"]["captured"],$minorUnits),true,false). "</td></tr>";

        //Total Refunded
        $res .= "<tr><td>" . Mage::helper('bamboracheckout')->__("Refunded:") . "</td>";
        $res .= "<td>".Mage::helper('core')->currency($bamboraCurrency->convertPriceFromMinorUnits($bamboraTransactionsJson["transaction"]["total"]["credited"],$minorUnits),true,false). "</td></tr>";

        $res .= "</table><br>";
        
        $res .= "<a href='https://merchant.bambora.com' target='_blank'>" . Mage::helper('bamboracheckout')->__("Go to the Bambora administration to handle your transactions") . "</a>";
        $res .= "<br><br>";
        
		return $res;
    }

    public function printLogo($cardid) {
    	return '<img class="bambora_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$cardid.'.png" />';
    }


    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return Mage::helper('sales')->__('Information');
    }

    public function getTabTitle()
    {
        return Mage::helper('sales')->__('Order Information');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}