<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Block_Checkout_Redirect extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        
        $bambora = Mage::getModel('bamboracheckout/bambora');
        
        $setCheckoutRequest = $bambora->createBamboraCheckoutRequest();
        $setCheckoutResponse = $bambora->setBamboraCheckout($setCheckoutRequest);

        //If payment window is set to full screen
        if($bambora->getConfigData('windowstate', $bambora->getStore()->getStoreId()) == 1)
        {
            Mage::app()->getResponse()->setRedirect($setCheckoutResponse["url"]);;
        }
        else{
        //For overlay
            $this->assign("data", array("paymentWindowUrl"=>$bambora->getBamboraPaymentWindowUrl(),
                                 "bamboraCheckoutUrl"=>$setCheckoutResponse["url"],
                                 "cancelUrl"=>$setCheckoutRequest->url->decline,
                                 "headerText"=>Mage::helper("bamboracheckout")->__("Thank you for using Bambora Checkout"),
                                 "headerText2"=>Mage::helper("bamboracheckout")->__("Please wait...")));
            $this->setTemplate('bambora/checkout/redirect_paymentwindow.phtml');
        }

        
    }


}
?>