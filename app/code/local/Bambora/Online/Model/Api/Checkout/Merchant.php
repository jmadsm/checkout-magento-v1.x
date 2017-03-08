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

class Bambora_Online_Model_Api_Checkout_Merchant extends Bambora_Online_Model_Api_Checkout_Base
{
    /**
     * Get the allowed payment types
     *
     * @param string $currency
     * @param int|long $amount
     * @param string $apiKey
     * @return Bambora_Online_Model_Api_Checkout_Response_ListPaymentTypes
     */
    public function getPaymentTypes($currency, $amount, $apiKey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_MERCHANT) . '/paymenttypes?currency='. $currency . '&amount=' . $amount;
            $resultJson = $this->_callRestService($serviceUrl, null, Zend_Http_Client::GET, $apiKey);
            $result = json_decode($resultJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_ListPaymentTypes */
            $listPaymentTypesResponse = Mage::getModel(Model::RESPONSE_LISTPAYMENTTYPES);
            $listPaymentTypesResponse->meta = $this->_mapMeta($result);

            if ($listPaymentTypesResponse->meta->result) {
                $listPaymentTypesResponse->paymentCollections = array();

                foreach ($result['paymentcollections'] as $payment) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_PaymentCollection */
                    $paymentCollection = Mage::getModel(Model::RESPONSE_MODEL_PAYMENTCOLLECTION);
                    $paymentCollection->displayName = $payment['displayname'];
                    $paymentCollection->id = $payment['id'];
                    $paymentCollection->name = $payment['name'];
                    $paymentCollection->paymentGroups = array();

                    foreach ($payment['paymentgroups'] as $group) {
                        /** @var Bambora_Online_Model_Api_Checkout_Response_Model_PaymentGroup */
                        $paymentGroup = Mage::getModel(Model::RESPONSE_MODEL_PAYMENTGROUP);
                        $paymentGroup->displayName = $group['displayname'];
                        $paymentGroup->id = $group['id'];
                        $paymentGroup->name = $group['name'];
                        $paymentGroup->paymentTypes = array();

                        foreach ($group['paymenttypes'] as $type) {
                            /** @var Bambora_Online_Model_Api_Checkout_Response_Model_PaymentType */
                            $paymentType = Mage::getModel(Model::RESPONSE_MODEL_PAYMENTYPE);
                            $paymentType->displayName = $type['displayname'];
                            $paymentType->groupid = $type['groupid'];
                            $paymentType->id = $type['id'];
                            $paymentType->name = $type['name'];

                            /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Fee */
                            $fee = Mage::getModel(Model::RESPONSE_MODEL_FEE);
                            $fee->amount = $type['fee']['amount'];
                            $fee->id = $type['fee']['id'];

                            $paymentType->fee = $fee;

                            $paymentGroup->paymentTypes[] = $paymentType;
                        }

                        $paymentCollection->paymentGroups[] = $paymentGroup;
                    }
                    $listPaymentTypesResponse->paymentCollections[] = $paymentCollection;
                }
            }

            return $listPaymentTypesResponse;
        } catch (Exception $ex) {
            $this->logException($ex);
            return null;
        }
    }

    /**
     * Returns a transaction based on the transactionid
     *
     * @param string $transactionId
     * @param string $apiKey
     * @return Bambora_Online_Model_Api_Checkout_Response_Transaction
     */
    public function getTransaction($transactionId, $apiKey)
    {
        try {
            $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_MERCHANT) . '/transactions/' . sprintf('%.0F', $transactionId);

            $resultJson = $this->_callRestService($serviceUrl, null, Zend_Http_Client::GET, $apiKey);
            $result = json_decode($resultJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_Transaction */
            $transactionResponse = Mage::getModel(Model::RESPONSE_TRANSACTION);
            $transactionResponse->meta = $this->_mapMeta($result);

            if ($transactionResponse->meta->result) {
                $result = $result['transaction'];

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Transaction */
                $transaction = Mage::getModel(Model::RESPONSE_MODEL_TRANSACTION);

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Available */
                $available = Mage::getModel(Model::RESPONSE_MODEL_AVAILABLE);
                $available->capture = $result['available']['capture'];
                $available->credit = $result['available']['credit'];

                $transaction->available = $available;
                $transaction->canDelete = $result['candelete'];
                $transaction->createdDate = $result['createddate'];

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Currency */
                $currency = Mage::getModel(Model::RESPONSE_MODEL_CURRENCY);
                $currency->code = $result['currency']['code'];
                $currency->minorunits = $result['currency']['minorunits'];
                $currency->name = $result['currency']['name'];
                $currency->number = $result['currency']['number'];

                $transaction->currency = $currency;
                $transaction->id = $result['id'];

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Information */
                $information = Mage::getModel(Model::RESPONSE_MODEL_INFORMATION);
                $information->acquirers = array();
                foreach ($result['information']['acquirers'] as $acq) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Acquirer */
                    $acquirer = Mage::getModel(Model::RESPONSE_MODEL_ACQUIRER);
                    $acquirer->name = $acq['name'];
                    $information->acquirers[] = $acquirer;
                }
                $information->paymentTypes = array();
                foreach ($result['information']['paymenttypes'] as $type) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_PaymentType */
                    $paymentType = Mage::getModel(Model::RESPONSE_MODEL_PAYMENTYPE);
                    $paymentType->displayName = $type['displayname'];
                    $paymentType->groupid = $type['groupid'];
                    $paymentType->id = $type['id'];
                    $information->paymentTypes[] = $paymentType;
                }
                $information->primaryAccountnumbers = array();
                foreach ($result['information']['primaryaccountnumbers'] as $accountNumber) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_PrimaryAccountnumber */
                    $primaryAccountnumber = Mage::getModel(Model::RESPONSE_MODEL_PRIMARYACCOUNTNUMBER);
                    $primaryAccountnumber->number = $accountNumber['number'];
                    $information->primaryAccountnumbers[] = $primaryAccountnumber;
                }

                $transaction->information = $information;

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Links */
                $links = Mage::getModel(Model::RESPONSE_MODEL_LINKS);
                $links->transactionoperations = $result['links']['transactionoperations'];

                $transaction->links = $links;
                $transaction->merchantnumber = $result['merchantnumber'];
                $transaction->orderid = $result['orderid'];
                $transaction->reference = $result['reference'];
                $transaction->status = $result['status'];

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Subscription */
                $subscription = Mage::getModel(Model::RESPONSE_MODEL_SUBSCRIPTION);
                $subscription->id = $result['subscription']['id'];

                $transaction->subscription = $subscription;

                /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Total */
                $total = Mage::getModel(Model::RESPONSE_MODEL_TOTAL);
                $total->authorized = $result['total']['authorized'];
                $total->balance = $result['total']['balance'];
                $total->captured = $result['total']['captured'];
                $total->credited = $result['total']['credited'];
                $total->declined = $result['total']['declined'];
                $total->feeamount = $result['total']['feeamount'];

                $transaction->total = $total;

                $transactionResponse->transaction = $transaction;
            }

            return $transactionResponse;
        } catch (Exception $ex) {
            $this->logException($ex);
            return null;
        }
    }

    /**
     * Returns a list of transaction operations based on the transactionid
     *
     * @param string $transactionId
     * @param string $apiKey
     * @return Bambora_Online_Model_Api_Checkout_Response_ListTransactionOperations
     */
    public function getTransactionOperations($transactionId, $apiKey)
    {
        $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_MERCHANT) . '/transactions/' . sprintf('%.0F', $transactionId).'/transactionoperations';

        $resultJson = $this->_callRestService($serviceUrl, null, Zend_Http_Client::GET, $apiKey);
        $result = json_decode($resultJson, true);

        /** @var Bambora_Online_Model_Api_Checkout_Response_ListTransactionOperations */
        $transactionOperationResponse = Mage::getModel(Model::RESPONSE_LISTTRANSACTIONOPERATIONS);
        $transactionOperationResponse->meta = $this->_mapMeta($result);

        if ($transactionOperationResponse->meta->result) {
            $operations = $result['transactionoperations'];
            $transactionOperations = $this->mapTransactionOperation($operations);
            $transactionOperationResponse->transactionOperations = $transactionOperations;
        }

        return $transactionOperationResponse;
    }

    /**
     * Summary of mapTransactionOperation
     *
     * @param mixed $operations
     * @return Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation[]
     */
    private function mapTransactionOperation($operations)
    {
        $transactionOperations = array();

        foreach ($operations as $operation) {
            /** @var Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation */
            $transactionOperation = Mage::getModel(Model::RESPONSE_MODEL_TRANSACTIONOPERATION);
            $transactionOperation->acquirername = $this->getValue($operation, 'acquirername');
            $transactionOperation->acquirerreference = $this->getValue($operation, 'acquirerreference');
            $transactionOperation->action = $this->getValue($operation, 'action');
            $transactionOperation->actionbysystem = $this->getValue($operation, 'actionbysystem');
            $transactionOperation->actioncode = $this->getValue($operation, 'actioncode');
            $transactionOperation->actionsource = $this->getValue($operation, 'actionsource');
            $transactionOperation->amount = $this->getValue($operation, 'amount');

            /** @var Bambora_Online_Model_Api_Checkout_Response_Model_ApiUser */
            $apiUser = Mage::getModel(Model::RESPONSE_MODEL_APIUSER);
            $apiUser->description = $this->getValue($operation, 'description');
            $apiUser->email = $this->getValue($operation, 'email');

            $transactionOperation->apiuser = $apiUser;
            $transactionOperation->clientipaddress = $this->getValue($operation, 'clientipaddress');
            $transactionOperation->createddate = $this->getValue($operation, 'createddate');

            /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Currency */
            $currency = Mage::getModel(Model::RESPONSE_MODEL_CURRENCY);
            $currency->code = $this->getValue($operation, 'code');
            $currency->minorunits = $this->getValue($operation, 'minorunits');
            $currency->name = $this->getValue($operation, 'name');
            $currency->number = $this->getValue($operation, 'number');

            $transactionOperation->currency = $currency;
            $transactionOperation->currentbalance = $this->getValue($operation, 'currentbalance');
            $transactionOperation->ecis = array();
            $ecis = $this->getValue($operation, 'ecis');
            if (is_array($ecis)) {
                foreach ($ecis as $ec) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_Ecis */
                    $eci = Mage::getModel(Model::RESPONSE_MODEL_ECIS);
                    $eci->value = $this->getValue($ec, 'value');
                    $transactionOperation->ecis[] = $eci;
                }
            }
            $transactionOperation->id = $this->getValue($operation, 'id');
            $transactionOperation->iscapturemulti = $this->getValue($operation, 'iscapturemulti');
            $transactionOperation->parenttransactionoperationid = $this->getValue($operation, 'parenttransactionoperationid');

            $transactionOperation->paymenttypes = array();
            $paymenttypes = $this->getValue($operation, 'paymenttypes');
            if (is_array($paymenttypes)) {
                foreach ($paymenttypes as $type) {
                    /** @var Bambora_Online_Model_Api_Checkout_Response_Model_PaymentType */
                    $paymentType = Mage::getModel(Model::RESPONSE_MODEL_PAYMENTYPE);
                    $paymentType->id = $this->getValue($type, 'id');
                    $transactionOperation->paymenttypes[] = $paymentType;
                }
            }

            $transactionOperation->status = $this->getValue($operation, 'status');
            $transactionOperation->subaction = $this->getValue($operation, 'subaction');

            $transactionoperations = $this->getValue($operation, 'transactionoperations');
            if (is_array($transactionoperations) && count($transactionoperations) > 0) {
                $transactionOperation->transactionoperations = $this->mapTransactionOperation($transactionoperations);
            }

            $transactionOperations[] = $transactionOperation;
        }
        return $transactionOperations;
    }

    private function getValue($array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            foreach ($array as $k=>$v) {
                if (!is_array($v)) {
                    continue;
                }
                if (array_key_exists($key, $v)) {
                    return $v[$key];
                }
            }
            return "";
        }
    }
}
