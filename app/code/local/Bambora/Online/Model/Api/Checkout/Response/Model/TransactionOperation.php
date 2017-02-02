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
