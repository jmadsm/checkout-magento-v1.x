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
use Bambora_Online_Helper_BamboraConstant as BamboarConstant;

class Bambora_Online_Block_Checkout_Form extends Mage_Payment_Block_Form
{
    public function _construct()
    {
        parent::_construct();

        $this->setTemplate('bambora/checkout/form.phtml');
    }

    /**
     * Get the html for the payment method
     *
     * @return string
     */
    public function getPaymentHtml()
    {
        /** @var Bambora_Online_Helper_Data */
        $bamboraHelper = Mage::helper('bambora');

        $res = '<ul id="payment_form_'. $this->getMethodCode().' style="display:none">';
        /** @var Bambora_Online_Model_Checkout_Payment */
        $paymentMethod =  Mage::getModel('bamboracheckout/payment');

        $res = "";
        if (intval($paymentMethod->getConfigData(BamboarConstant::ONLY_SHOW_PAYMENT_LOGOS)) === 0) {
            $res = '<li>'. $bamboraHelper->_s("You have chosen to pay for the order online. Once you've completed your order, you will be transferred to the Bambora Checkout. Here you need to process your payment. Once payment is completed, you will automatically be returned to our shop.") .'</li>';
        }
        $paymentCards = $paymentMethod->getPaymentCardIds();
        if (!empty($paymentCards)) {
            $res .= '<li>';
            foreach ($paymentCards as $paymentcard) {
                $res .= '<img class="bambora_form_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$paymentcard.'.svg" />';
            }
            $res .= '</li>';
        }
        $res .= '</ul>';

        return $res;
    }
}
