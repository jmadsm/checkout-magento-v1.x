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
class Bambora_Online_Block_Checkout_Info extends Mage_Payment_Block_Info
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if ($this->_paymentSpecificInformation !== null) {
            return $this->_paymentSpecificInformation;
        }
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);

        $info = $this->getInfo();
        $order = $info->getOrder();
        if (!isset($order)) {
            return $transport;
        }

        $payment = $order->getPayment();
        $transactionId = $payment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
        $paymentType = $payment->getCcType();
        $truncatedCardNumberEncrypted = $payment->getCcNumberEnc();
        $truncatedCardNumber = "";
        if(!empty($truncatedCardNumberEncrypted) && strpos($truncatedCardNumberEncrypted, "XXXX") === false) {
            $truncatedCardNumber = Mage::helper('core')->decrypt($truncatedCardNumberEncrypted);
        } else {
            $truncatedCardNumber = $truncatedCardNumberEncrypted;
        }

        /** @var Bambora_Online_Helper_Data */
        $bamboraHelper = Mage::helper('bambora');

        if (!empty($transactionId)) {
            $key = $bamboraHelper->_s("Transaction ID");
            $transport->addData(array($key => $transactionId));
        }

        if (!empty($paymentType)) {
            $key = $bamboraHelper->_s("Card type");
            $transport->addData(array($key => $paymentType));
        }

        if (!empty($truncatedCardNumber)) {
            $key = $bamboraHelper->_s("Card number");
            $transport->addData(array($key => $truncatedCardNumber));
        }

        return $transport;
    }
}
