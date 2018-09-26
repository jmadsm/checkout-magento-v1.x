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

use Bambora_Online_Helper_BamboraConstant as BamboraConstant;

class Bambora_Online_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * @var Bambora_Online_Helper_Data
     */
    private $bamboraHelper;

    protected function _construct()
    {
        parent::_construct();
        $this->bamboraHelper = Mage::helper('bambora');
        $this->setTemplate('bambora/order/view/tab/info.phtml');
    }

    /**
     * Get the current order
     *
     * @return Mage_Sales_Model_Order
     */
    private function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Return true if the remote interface is enabled
     *
     * @return boolean
     */
    private function isRemoteInterfaceEnabled()
    {
        $order = $this->getOrder();
        return intval($order->getPayment()->getMethodInstance()->getConfigData(BamboraConstant::REMOTE_INTERFACE, $order->getStoreId())) === 1;
    }

    /**
     * Generate the Bambora Payment information html
     *
     * @return string
     */
    public function getPaymentInformationHtml()
    {
        if (!$this->isRemoteInterfaceEnabled()) {
            return $this->bamboraHelper->_s("Please enable remote payment processing from the module configuration");
        }
        try {
            $order = $this->getOrder();
            /** @var Bambora_Online_Model_Checkout_Payment $paymentMethod */
            $paymentMethod = $order->getPayment()->getMethodInstance();
            $transactionId = $order->getPayment()->getAdditionalInformation($paymentMethod::PSP_REFERENCE);

            if (empty($transactionId)) {
                return $this->bamboraHelper->_s("There is not registered any payment for this order yet!");
            }

            $bamboraTransaction = $paymentMethod->getTransaction($transactionId, $order);
            $paymentInfoHtml = $this->createCheckoutTransactionHtml($bamboraTransaction);

            $bamboraTransactionOperations = $paymentMethod->getTransactionOperations($transactionId, $order);
            if (count($bamboraTransactionOperations) > 0) {
                $paymentInfoHtml .= $this->createCheckoutTransactionOperationsHtml($bamboraTransactionOperations);
            }

            return $paymentInfoHtml;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Create Checkout Transaction HTML
     *
     * @param Bambora_Online_Model_Api_Checkout_Response_Transaction $transaction
     * @return string
     */
    private function createCheckoutTransactionHtml($transaction)
    {
        $res = "<table border='0' width='100%'>";
        $res .= '<tr><td colspan="2" class="bambora_table_title"><strong>Bambora Checkout</strong></td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Transaction ID') . ':</td>';
        $res .= '<td>' . $transaction->id . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Amount') . ':</td>';
        $amount = $this->bamboraHelper->convertPriceFromMinorunits($transaction->total->authorized, $transaction->currency->minorunits);
        $res .= '<td>' . Mage::helper('core')->currency($amount, true, false) . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Transaction date') . ':</td>';
        $res .= '<td>' . $this->formatDate($transaction->createdDate, Mage_Core_Model_Locale::FORMAT_TYPE_SHORT, true) . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Card type') . ':</td>';
        $res .= '<td>' . $transaction->information->paymentTypes[0]->displayName . $this->getPaymentLogoUrl($transaction->information->paymentTypes[0]->groupid). '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Card number') . ':</td>';
        $res .= '<td>' . $transaction->information->primaryAccountnumbers[0]->number . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Surcharge fee') . ':</td>';
        $surchargeFee = $this->bamboraHelper->convertPriceFromMinorunits($transaction->total->feeamount, $transaction->currency->minorunits);
        $res .= '<td>' . Mage::helper('core')->currency($surchargeFee, true, false) . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Captured') . ':</td>';
        $capturedAmount = $this->bamboraHelper->convertPriceFromMinorunits($transaction->total->captured, $transaction->currency->minorunits);
        $res .= '<td>' . Mage::helper('core')->currency($capturedAmount, true, false) . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Refunded') . ':</td>';
        $creditedAmount = $this->bamboraHelper->convertPriceFromMinorunits($transaction->total->credited, $transaction->currency->minorunits);
        $res .= '<td>' . Mage::helper('core')->currency($creditedAmount, true, false) . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Acquirer') . ':</td>';
        $res .= '<td>' . $transaction->information->acquirers[0]->name . '</td></tr>';

        $res .= '<tr><td>' . $this->bamboraHelper->_s('Status') . ':</td>';
        $res .= '<td>' . $this->checkoutStatus($transaction->status) . '</td></tr>';
        $res .= "</table>";
        return $res;
    }

    /**
     * Set the first letter to uppercase
     *
     * @param string $status
     * @return string
     */
    private function checkoutStatus($status)
    {
        if (!isset($status)) {
            return "";
        }
        $firstLetter = substr($status, 0, 1);
        $firstLetterToUpper = strtoupper($firstLetter);
        $result = str_replace($firstLetter, $firstLetterToUpper, $status);

        return $result;
    }

    /**
     * Create html for paymentLogoUrl
     *
     * @param mixed $paymentId
     * @return string
     */
    private function getPaymentLogoUrl($paymentId)
    {
        return '<img class="bambora_paymentcard" src="https://d3r1pwhfz7unl9.cloudfront.net/paymentlogos/'.$paymentId . '.svg"';
    }


    /**
     * Create Checkout Transaction Operation HTML
     *
     * @param Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation[] $transactionOperations
     * @return string
     */
    private function createCheckoutTransactionOperationsHtml($transactionOperations)
    {
        $res = '<br/>';
        $res .= "<table border='0' width='100%'>";
        $res .= '<tr><td colspan="6" class="bambora_operations_title"><strong>'.__('Transaction Operations'). '</strong></td></tr>';
        $res .= '<tr><th>'.$this->bamboraHelper->_s("Date").'</th>';
        $res .= '<th>'.$this->bamboraHelper->_s("Action").'</th>';
        $res .= '<th>'.$this->bamboraHelper->_s("Amount").'</th>';
        $res .= '<th>'.$this->bamboraHelper->_s("ECI").'</th>';
        $res .= '<th>'.$this->bamboraHelper->_s("Operation ID").'</th>';
        $res .= '<th>'.$this->bamboraHelper->_s("Parent Operation ID").'</th></tr>';

        $res .= $this->createTranactionOperationItems($transactionOperations);

        $res .= '</table>';

        return $res;
    }

    /**
     * Create Checkout Transaction Operation items HTML
     *
     * @param Bambora_Online_Model_Api_Checkout_Response_Model_TransactionOperation[] $transactionOperations
     * @param string $res
     */
    private function createTranactionOperationItems($transactionOperations)
    {
        $res = "";
        foreach ($transactionOperations as $operation) {
            $res .= '<tr>';
            $res .= '<td>' . $this->formatDate($operation->createddate, Mage_Core_Model_Locale::FORMAT_TYPE_SHORT, true).'</td>' ;
            $res .= '<td>' . $operation->action  .'</td>';
            $amount = $this->bamboraHelper->convertPriceFromMinorunits($operation->amount, $operation->currency->minorunits);
            $res .= '<td>' . Mage::helper('core')->currency($amount, true, false)  . '</td>';

            if (is_array($operation->ecis) && count($operation->ecis)> 0) {
                $res .= '<td>' . $operation->ecis[0]->value .'</td>';
            } else {
                $res .= '<td> - </td>';
            }

            $res .= '<td>' . $operation->id . '</td>';

            if ($operation->parenttransactionoperationid > 0) {
                $res .= '<td>' . $operation->parenttransactionoperationid .'</td>';
            } else {
                $res .= '<td> - </td>';
            }

            if (count($operation->transactionoperations) > 0) {
                $res .= $this->createTranactionOperationItems($operation->transactionoperations);
            }
            $res .= '</tr>';
        }

        return $res;
    }


    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return 'Bambora Online Checkout';
    }

    public function getTabTitle()
    {
        return 'Bambora Online Checkout';
    }

    public function canShowTab()
    {
        /** @var Mage_Sales_Model_Order_Payment $payment */
        $payment = $this->getOrder()->getPayment();
        if ($payment->getMethod() === Bambora_Online_Model_Checkout_Payment::METHOD_CODE && $this->isRemoteInterfaceEnabled()) {
            return true;
        }
        return false;
    }

    public function isHidden()
    {
        return false;
    }
}
