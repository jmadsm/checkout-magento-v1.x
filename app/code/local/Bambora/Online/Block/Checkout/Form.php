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
                $res .= '<img class="bambora_form_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$paymentcard.'.png" />';
            }
            $res .= '</li>';
        }
        $res .= '</ul>';

        return $res;
    }
}
