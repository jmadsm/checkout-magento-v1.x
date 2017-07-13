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

class Bambora_Online_Model_Checkout_Observer
{
    public function addMassOrderAction($event)
    {
        /** @var Bambora_Online_Helper_Data */
        $bamboraHelper = Mage::helper('bambora');
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction && $block->getRequest()->getControllerName() == 'sales_order') {
            $block->addItem('bambora_capture', array(
             'label'=> $bamboraHelper->_s("Bambora - Mass Invoice and Capture"),
             'url'  => $block->getUrl('adminhtml/massaction/bamboramasscapture'),
             'confirm' => $bamboraHelper->_s("Are you sure you want to invoice and capture selected items?")
             ));

            $block->addItem('bambora_delete', array(
             'label'=> $bamboraHelper->_s("Bambora - Mass Delete"),
             'url'  => $block->getUrl('adminhtml/massaction/bamboramassdelete'),
             'confirm' => $bamboraHelper->_s("Are you sure you want to delete selected items? This can not be undone! If there have been authorized a payment on the order it will not get voided by this action.")
             ));
        }
    }

    public function addMassInvoiceAction($event)
    {
        /** @var Bambora_Online_Helper_Data */
        $bamboraHelper = Mage::helper('bambora');
        $block = $event->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Widget_Grid_Massaction && $block->getRequest()->getControllerName() == 'sales_invoice') {
            $block->addItem('bambora_invoice', array(
             'label'=> $bamboraHelper->_s("Bambora - Mass Creditmemo and Refund"),
             'url'  => $block->getUrl('adminhtml/massaction/bamboramassrefund'),
             'confirm' => $bamboraHelper->_s("Are you sure you want to refund selected items?")
             ));
        }
    }

    /**
     * Auto Cancel orders that are from 1 day until 1 hour ago and with custom pending status
     */
    public function autocancelPendingOrders()
    {
        /** @var Bambora_Online_Helper_Data */
        $bamboraHelper = Mage::helper('bambora');
        /** @var Bambora_Online_Model_Checkout_Payment */
        $payment = Mage::getModel('bamboracheckout/payment');
        $storeId = $payment->getStore()->getId();

        if (intval($payment->getConfigData(BamboraConstant::USE_AUTO_CANCEL, $storeId)) === 1) {
            $date = Mage::getSingleton('core/date');

            $orderCollection = Mage::getResourceModel('sales/order_collection');

            $orderCollection
                ->addFieldToFilter('status', array('eq' => $payment->getConfigData(BamboraConstant::ORDER_STATUS, null)))
                ->addFieldToFilter('created_at', array(
                    'to' => strtotime('-1 hour', strtotime($date->gmtDate())),
                    'from' => strtotime('-1 day', strtotime($date->gmtDate())),
                    'datetime' => true))
                ->setOrder('created_at', 'ASC')
                ->getSelect();

            foreach ($orderCollection->getItems() as $order) {
                /** @var Mage_Sales_Model_Order */
                $orderModel = Mage::getModel('sales/order');
                $orderModel->load($order["entity_id"]);

                try {
                    if (!$this->validatePaymentMethod($orderModel)) {
                        continue;
                    }

                    if (!$orderModel->canCancel()) {
                        continue;
                    }

                    $pspReference = $order->getPayment()->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
                    if (!empty($pspReference)) {
                        continue;
                    }

                    $orderModel->cancel();
                    $message = $bamboraHelper->_s("Order was auto canceled because no payment has been made.");
                    $orderModel->addStatusToHistory($orderModel->getStatus(), $message);
                    $orderModel->save();
                } catch (Exception $e) {
                    $message = "Could not be canceled: " . $e->getMessage();
                    $orderModel->addStatusToHistory($orderModel->getStatus(), $message);
                    Mage::logException($e);
                }
            }
        }
    }

    public function orderPlacedAfter($observer)
    {
        /** @var Mage_Sales_Model_Order */
        $order = $observer->getOrder();
        if ($this->validatePaymentMethod($order)) {
            $order->addStatusHistoryComment(Mage::helper('bambora')->_s("The Order is placed using Bambora Checkout and is now awaiting payment."))
                ->setIsCustomerNotified(false);
            $order->save();
        }
    }

    private function validatePaymentMethod($order)
    {
        $currentMethod = $order->getPayment()->getMethod();
        return $currentMethod === Bambora_Online_Model_Checkout_Payment::METHOD_CODE;
    }
}
