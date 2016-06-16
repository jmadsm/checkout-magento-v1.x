<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Model_BamboraModels_Orderline extends Mage_Core_Model_Abstract
{
    public $description;
    public $id; //sku
    public $linenumber;
    public $quantity;
    public $text;
    public $totalprice;
    public $totalpriceinclvat;
    public $totalpricevatamount;
    public $unit;
    public $unitprice;
    public $unitpriceinclvat;
    public $unitpricevatamount;
    public $vat;
}
?>