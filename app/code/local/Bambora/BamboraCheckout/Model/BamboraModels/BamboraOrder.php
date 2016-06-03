<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Model_BamboraModels_BamboraOrder extends Mage_Core_Model_Abstract
{
    public $billingaddress; // of type BamboraAddress
    public $currency;
    public $lines;
    public $ordernumber;
    public $shippingaddress; // of type BamboraAddress       
    public $total;
    public $vatamount;
}
?>