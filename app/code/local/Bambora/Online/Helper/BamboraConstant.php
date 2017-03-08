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
class Bambora_Online_Helper_BamboraConstant
{
    //Surcharge
    const BAMBORA_SURCHARGE = 'surcharge_fee';

    //Config constants
    const ORDER_STATUS = 'order_status';
    const ORDER_STATUS_AFTER_PAYMENT = 'order_status_after_payment';
    const MASS_CAPTURE_INVOICE_MAIL = 'masscaptureinvoicemail';
    const TITLE = 'title';
    const MERCHANT_NUMBER = 'merchantnumber';
    const ACCESS_TOKEN = 'accesstoken';
    const SECRET_TOKEN = 'secrettoken';
    const MD5_KEY = 'md5key';
    const PAYMENT_WINDOW_ID = 'paymentwindowid';
    const INSTANT_CAPTURE = 'instantcapture'; //DONT CHANTE THIS
    const INSTANT_INVOICE = 'instantinvoice';
    const INSTANT_INVOICE_MAIL = 'instantinvoicemail';
    const IMMEDIATEREDI_REDIRECT_TO_ACCEPT = 'immediateredirecttoaccept';
    const ADD_SURCHARGE_TO_PAYMENT = 'addsurchargetopayment';
    const SEND_MAIL_ORDER_CONFIRMATION = 'sendmailorderconfirmation';
    const WINDOW_STATE = 'windowstate';
    const REMOTE_INTERFACE = 'remoteinterface';
    const ONLY_SHOW_PAYMENT_LOGOS = 'onlyshowpaymentlogos';
    const USE_AUTO_CANCEL = 'useautocancel';

    //Actions
    const CAPTURE = 'capture';
    const REFUND = 'refund';
    const VOID = 'void';
    const GET_TRANSACTION = 'gettransaction';
}
