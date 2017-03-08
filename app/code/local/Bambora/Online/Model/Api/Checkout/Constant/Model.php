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
class Bambora_Online_Model_Api_Checkout_Constant_Model
{
    // Request
    const REQUEST_CHECKOUT = 'bamboracheckoutrequest/Checkout';
    const REQUEST_CAPTURE = 'bamboracheckoutrequest/Capture';
    const REQUEST_CREDIT = 'bamboracheckoutrequest/Credit';

    // Request Models
    const REQUEST_MODEL_ADDRESS = 'bamboracheckoutrequestmodel/Address';
    const REQUEST_MODEL_CALLBACK = 'bamboracheckoutrequestmodel/Callback';
    const REQUEST_MODEL_CUSTOMER = 'bamboracheckoutrequestmodel/Customer';
    const REQUEST_MODEL_ORDER = 'bamboracheckoutrequestmodel/Order';
    const REQUEST_MODEL_LINE = 'bamboracheckoutrequestmodel/Line';
    const REQUEST_MODEL_URL = 'bamboracheckoutrequestmodel/Url';

    //Response
    const RESPONSE_CHECKOUT = 'bamboracheckoutresponse/Checkout';
    const RESPONSE_LISTPAYMENTTYPES = 'bamboracheckoutresponse/ListPaymentTypes';
    const RESPONSE_TRANSACTION = 'bamboracheckoutresponse/Transaction';
    const RESPONSE_CAPTURE = 'bamboracheckoutresponse/Capture';
    const RESPONSE_CREDIT = 'bamboracheckoutresponse/Credit';
    const RESPONSE_DELETE = 'bamboracheckoutresponse/Delete';
    const RESPONSE_LISTTRANSACTIONOPERATIONS = 'bamboracheckoutresponse/ListTransactionOperations';

    //Response Models
    const RESPONSE_MODEL_META = 'bamboracheckoutresponsemodel/Meta';
    const RESPONSE_MODEL_MESSAGE = 'bamboracheckoutresponsemodel/Message';
    const RESPONSE_MODEL_PAYMENTCOLLECTION = 'bamboracheckoutresponsemodel/PaymentCollection';
    const RESPONSE_MODEL_PAYMENTGROUP = 'bamboracheckoutresponsemodel/PaymentGroup';
    const RESPONSE_MODEL_PAYMENTYPE = 'bamboracheckoutresponsemodel/PaymentType';
    const RESPONSE_MODEL_FEE = 'bamboracheckoutresponsemodel/Fee';
    const RESPONSE_MODEL_AVAILABLE = 'bamboracheckoutresponsemodel/Available';
    const RESPONSE_MODEL_CURRENCY = 'bamboracheckoutresponsemodel/Currency';
    const RESPONSE_MODEL_INFORMATION = 'bamboracheckoutresponsemodel/Information';
    const RESPONSE_MODEL_ACQUIRER = 'bamboracheckoutresponsemodel/Acquirer';
    const RESPONSE_MODEL_PRIMARYACCOUNTNUMBER = 'bamboracheckoutresponsemodel/PrimaryAccountnumber';
    const RESPONSE_MODEL_LINKS = 'bamboracheckoutresponsemodel/Links';
    const RESPONSE_MODEL_SUBSCRIPTION = 'bamboracheckoutresponsemodel/Subscription';
    const RESPONSE_MODEL_TOTAL = 'bamboracheckoutresponsemodel/Total';
    const RESPONSE_MODEL_TRANSACTION = 'bamboracheckoutresponsemodel/Transaction';
    const RESPONSE_MODEL_TRANSACTIONOPERATION = 'bamboracheckoutresponsemodel/TransactionOperation';
    const RESPONSE_MODEL_APIUSER = 'bamboracheckoutresponsemodel/ApiUser';
    const RESPONSE_MODEL_ECIS = 'bamboracheckoutresponsemodel/Ecis';
}
