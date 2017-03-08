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
class Bambora_Online_Model_Api_Checkout_Response_Model_Transaction
{
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Available
     */
    public $available;
    /**
     * @var bool
     */
    public $canDelete;
    /**
     * @var string
     */
    public $createdDate;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Currency
     */
    public $currency;
    /**
     * @var string
     */
    public $id;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Information
     */
    public $information;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Links
     */
    public $links;
    /**
     * @var string
     */
    public $merchantnumber;
    /**
     * @var string
     */
    public $orderid;
    /**
     * @var string
     */
    public $reference;
    /**
     * @var string
     */
    public $status;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Subscription
     */
    public $subscription;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Total
     */
    public $total;
}
