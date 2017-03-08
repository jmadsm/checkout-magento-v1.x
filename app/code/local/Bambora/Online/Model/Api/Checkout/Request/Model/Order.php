<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
class Bambora_Online_Model_Api_Checkout_Request_Model_Order
{
    /**
     * @var Bambora_Online_Model_Api_Checkout_Request_Model_Address
     */
    public $billingaddress;
    /**
     * @var string
     */
    public $currency;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Request_Model_Line[]
     */
    public $lines;
    /**
     * @var string
     */
    public $ordernumber;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Request_Model_Address
     */
    public $shippingaddress;
    /**
     * @var long
     */
    public $total;
    /**
     * @var long
     */
    public $vatamount;
}
