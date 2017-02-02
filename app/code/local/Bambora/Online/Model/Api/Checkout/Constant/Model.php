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
