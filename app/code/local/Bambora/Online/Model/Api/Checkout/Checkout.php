<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 *
 */
use Bambora_Online_Model_Api_Checkout_Constant_Endpoint as Endpoint;
use Bambora_Online_Model_Api_Checkout_Constant_Model as Model;

class Bambora_Online_Model_Api_Checkout_Checkout extends Bambora_Online_Model_Api_Checkout_Base
{
    /**
     * The endpoint for the Api service
     *
     * @param string $serviceEndpoint
     */
    private $serviceEndpoint;

    /**
     * Constructor
     */
    function __construct() {
        $this->serviceEndpoint = $this->_getEndpoint(Endpoint::ENDPOINT_CHECKOUT);
    }

    /**
     * Create the checkout request
     *
     * @param Bambora_Online_Model_Api_Checkout_Request_Checkout $setcheckoutrequest
     * @param string $apiKey
     * @return Bambora_Online_Model_Api_Checkout_Response_Checkout
     */
    public function setCheckout($setcheckoutrequest, $apiKey)
    {
        try {
            $serviceUrl = "{$this->serviceEndpoint}/checkout";
            $jsonData = json_encode($setcheckoutrequest);
            $checkoutResponseJson = $this->_callRestService($serviceUrl, $jsonData, Zend_Http_Client::POST, $apiKey);
            $checkoutResponseArray = json_decode($checkoutResponseJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_Checkout */
            $checkoutResponse = Mage::getModel(Model::RESPONSE_CHECKOUT);
            $checkoutResponse->meta = $this->_mapMeta($checkoutResponseArray);
            $checkoutResponse->token = $checkoutResponseArray['token'];
            $checkoutResponse->url = $checkoutResponseArray['url'];

            return $checkoutResponse;
        } catch (Exception $ex) {
            $this->logException($ex);
            return null;
        }
    }
}
