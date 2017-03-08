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
class Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation
{
    /**
     * @var string
     */
    public $acquirername;
    /**
     * @var string
     */
    public $acquirerreference;
    /**
     * @var string
     */
    public $action;
    /**
     * @var string
     */
    public $actionbysystem;
    /**
     * @var string
     */
    public $actioncode;
    /**
     * @var string
     */
    public $actionsource;
    /**
     * @var int|long
     */
    public $amount;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_ApiUser
     */
    public $apiuser;
    /**
     * @var string
     */
    public $clientipaddress;
    /**
     * @var string
     */
    public $createddate;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Currency
     */
    public $currency;
    /**
     * @var int|long
     */
    public $currentbalance;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_Ecis[]
     */
    public $ecis;
    /**
     * @var string
     */
    public $id;
    /**
     * @var bool
     */
    public $iscapturemulti;
    /**
     * @var string
     */
    public $parenttransactionoperationid;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_PaymentType[]
     */
    public $paymenttypes;
    /**
     * @var string
     */
    public $status;
    /**
     * @var string
     */
    public $subaction;
    /**
     * @var Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation[]
     */
    public $transactionoperations;
}
