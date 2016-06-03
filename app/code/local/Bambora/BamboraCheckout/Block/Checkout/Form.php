<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Block_Checkout_Form extends Mage_Payment_Block_Form
{
    public function __construct()
    {
        parent::_construct();
      
        $this->setTemplate('bambora/checkout/form.phtml');   
    }

    public function getPaymentCards()
    {
        $bambora =  Mage::getModel('bamboracheckout/bambora');
        return $bambora->getMerchantPaymentcards();
    }
}
?>