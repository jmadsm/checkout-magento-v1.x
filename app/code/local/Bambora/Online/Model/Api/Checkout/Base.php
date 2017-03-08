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
use Bambora_Online_Model_Api_Checkout_Constant_Model as Model;

abstract class Bambora_Online_Model_Api_Checkout_Base
{
    /**
     * List of Checkout endpoints
     *
     * @return array
     */
    private $endpoints = array(
        'merchant' => 'https://merchant-v1.api.epay.eu',
        'checkout' => 'https://api.v1.checkout.bambora.com',
        'transaction' => 'https://transaction-v1.api.epay.eu',
        'checkoutAssets' => 'https://v1.checkout.bambora.com/Assets',
        'globalAssets' => 'https://d3r1pwhfz7unl9.cloudfront.net/bambora'
    );

    /**
     * Return the address of the endpoint type
     *
     * @param string $type
     * @return string
     */
    public function _getEndpoint($type)
    {
        return $this->endpoints[$type];
    }

    /**
     * Sends the curl request to the given serviceurl
     *
     * @param string $serviceUrl
     * @param mixed $jsonData
     * @param string $method //POST OR GET
     * @param string $apiKey
     * @return mixed
     */
    protected function _callRestService($serviceUrl, $jsonData, $method, $apiKey)
    {
        $contentLength = isset($jsonData) ? strlen($jsonData) : 0;
        $headers = array(
           'Content-Type: application/json',
           'Content-Length: '. $contentLength,
           'Accept: application/json',
           'Authorization: ' . $apiKey,
           'X-EPay-System: ' . Mage::helper('bambora')->getCmsInfo()
       );

        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            'verifypeer' => false,
            'header' => false));
        $curl->write($method, $serviceUrl, '1.1', $headers, $jsonData);
        $result = $curl->read();

        $curl->close();

        return $result;
    }

    /**
     * Map bambora checkout response meta json to meta object
     *
     * @param mixed $response
     * @return Bambora_Online_Model_Api_Checkout_Response_Model_Meta|null
     */
    protected function _mapMeta($response)
    {
        if (!isset($response)) {
            return null;
        }
        /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Message */
        $message = Mage::getModel(Model::RESPONSE_MODEL_MESSAGE);
        $message->enduser = $response['meta']['message']['enduser'];
        $message->merchant = $response['meta']['message']['merchant'];

        /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Meta */
        $meta = Mage::getModel(Model::RESPONSE_MODEL_META);
        $meta->message = $message;
        $meta->result = $response['meta']['result'];

        return $meta;
    }

    protected function logException($exception)
    {
        /** @var Bambora_Online_Helper_Data $bamboraHelper */
        $bamboraHelper = Mage::helper('bambora');
        $bamboraHelper->logException($exception);
    }
}
