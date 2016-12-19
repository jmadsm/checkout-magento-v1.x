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
class Bambora_Online_Block_Checkout_Info extends Mage_Payment_Block_Info
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if ($this->_paymentSpecificInformation !== null)
        {
            return $this->_paymentSpecificInformation;
        }
        $transport = new Varien_Object();
        $transport = parent::_prepareSpecificInformation($transport);

        $info = $this->getInfo();
        $order = $info->getOrder();
        if(!isset($order))
        {
            return $transport;
        }

        $payment = $order->getPayment();
        $transactionId = $payment->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
        $paymentType = $payment->getCcType();
        $truncatedCardNumber = $payment->getCcNumberEnc();

        /** @var Bambora_Online_Helper_Data */
        $bamboraHelper = Mage::helper('bambora');

        if(!empty($transactionId))
        {
            $key = $bamboraHelper->_s("Transaction ID");
            $transport->addData(array($key => $transactionId));
        }

        if(!empty($paymentType))
        {
            $key = $bamboraHelper->_s("Card type");
            $transport->addData(array($key => $paymentType));
        }

        if(!empty($truncatedCardNumber))
        {
            $key = $bamboraHelper->_s("Card number");
            $transport->addData(array($key => $truncatedCardNumber));
        }

        return $transport;
    }
}