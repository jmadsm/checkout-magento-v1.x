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
use Bambora_Online_Model_Api_Checkout_Constant_Endpoint as Endpoint;
use Bambora_Online_Model_Api_Checkout_Constant_Model as Model;

class Bambora_Online_Model_Api_Checkout_Transaction extends Bambora_Online_Model_Api_Checkout_Base
{
    /**
     * Capture an amount for a given transaction
     *
     * @param string $transactionId
     * @param Bambora_Online_Model_Api_Checkout_Request_Capture $captureRequest
     * @param string $apikey
     * @return Bambora_Online_Model_Api_Checkout_Response_Capture
     */
    public function capture($transactionId, $captureRequest, $apikey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_TRANSACTION) .'/transactions/'.  sprintf('%.0F', $transactionId) . '/capture';

            $captureRequestJson = json_encode($captureRequest);

            $resultJson = $this->_callRestService($serviceUrl, $captureRequestJson, Zend_Http_Client::POST, $apikey);
            $result = json_decode($resultJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_Capture */
            $captureResponse = Mage::getModel(Model::RESPONSE_CAPTURE);
            $captureResponse->meta = $this->_mapMeta($result);

            if ($captureResponse->meta->result) {
                $captureResponse->transactionOperations = array();
                foreach ($result['transactionoperations'] as $operation) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation */
                    $transactionOperation = Mage::getModel(Model::RESPONSE_MODEL_TRANSACTIONOPERATION);
                    $transactionOperation->id = $operation['id'];
                    $captureResponse->transactionOperations[] = $transactionOperation;
                }
            }

            return $captureResponse;
        } catch (Exception $ex) {
            $this->logException($ex);
            return null;
        }
    }

    /**
     * Credit an amount for a given transaction
     *
     * @param string $transactionId
     * @param Bambora_Online_Model_Api_Checkout_Request_Credit $creditRequest
     * @param string $apikey
     * @return Bambora_Online_Model_Api_Checkout_Response_Capture
     */
    public function credit($transactionId, $creditRequest, $apikey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_TRANSACTION).'/transactions/'.  sprintf('%.0F', $transactionId) . '/credit';
            $creditRequestJson = json_encode($creditRequest);

            $resultJson = $this->_callRestService($serviceUrl, $creditRequestJson, Zend_Http_Client::POST, $apikey);
            $result = json_decode($resultJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_Credit */
            $creditResponse = Mage::getModel(Model::RESPONSE_CREDIT);
            $creditResponse->meta = $this->_mapMeta($result);

            if ($creditResponse->meta->result) {
                $creditResponse->transactionOperations = array();
                foreach ($result['transactionoperations'] as $operation) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation */
                    $transactionOperation = Mage::getModel(Model::RESPONSE_MODEL_TRANSACTIONOPERATION);
                    $transactionOperation->id = $operation['id'];
                    $creditResponse->transactionOperations[] = $transactionOperation;
                }
            }

            return $creditResponse;
        } catch (Exception $ex) {
            $this->logException($ex);
            return null;
        }
    }

    /**
     * Detete a transaction
     *
     * @param string $transactionId
     * @param string $apikey
     * @return Bambora_Online_Model_Api_Checkout_Response_Delete
     */
    public function delete($transactionId, $apikey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_TRANSACTION).'/transactions/'.  sprintf('%.0F', $transactionId) . '/delete';
            $resultJson = $this->_callRestService($serviceUrl, null, Zend_Http_Client::POST, $apikey);
            $result = json_decode($resultJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_Delete */
            $deleteResponse = Mage::getModel(Model::RESPONSE_DELETE);
            $deleteResponse->meta = $this->_mapMeta($result);

            if ($deleteResponse->meta->result) {
                $deleteResponse->transactionOperations = array();
                foreach ($result['transactionoperations'] as $operation) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation */
                    $transactionOperation = Mage::getModel(Model::RESPONSE_MODEL_TRANSACTIONOPERATION);
                    $transactionOperation->id = $operation['id'];
                    $deleteResponse->transactionOperations[] = $transactionOperation;
                }
            }

            return $deleteResponse;
        } catch (Exception $ex) {
            $this->logException($ex);
            return null;
        }
    }
}
