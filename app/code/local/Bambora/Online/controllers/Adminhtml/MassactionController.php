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

class Bambora_Online_Adminhtml_MassactionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var Bambora_Online_Helper_Data
     */
    private $bamboraHelper;


    public function _construct()
    {
        $this->bamboraHelper = Mage::helper('bambora');
    }

    /**
     * Mass Invoice and Capture Action
     */
    public function bamboraMassCaptureAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $method = Mage::getModel('bamboracheckout/payment');
        $countInvoicedOrder = 0;
        $invoiced = array();
        $notInvoiced = array();

        foreach ($orderIds as $orderId) {
            $order = Mage::getModel('sales/order');
            try {
                $order = $order->load($orderId);

                if (!$order->canInvoice()) {
                    $notInvoiced[] = $order->getIncrementId(). '('.$this->bamboraHelper->_s("Invoice not available"). ')';
                    continue;
                }

                $pspReference = $order->getPayment()->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
                if (empty($pspReference)) {
                    $notInvoiced[] = $order->getIncrementId() . '('.$this->bamboraHelper->_s("Bambora transaction not found"). ')';
                    continue;
                }

                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();

                $transactionSave = Mage::getModel('core/resource_transaction')
                  ->addObject($invoice)
                  ->addObject($invoice->getOrder());
                $transactionSave->save();
                $storeId =  $order->getStoreId();
                if (intval($method->getConfigData(BamboraConstant::MASS_CAPTURE_INVOICE_MAIL, $storeId)) == 1) {
                    $invoice->sendEmail();
                    $order->addStatusHistoryComment(sprintf($this->bamboraHelper->_s("Notified customer about invoice #%s"), $invoice->getId()))
                        ->setIsCustomerNotified(true);
                    $order->save();
                }

                $countInvoicedOrder++;
                $invoiced[] = $order->getIncrementId();
            } catch (Exception $e) {
                $notInvoiced[] = $order->getIncrementId();
                $this->_getSession()->addError(sprintf($this->bamboraHelper->_s("Order: %s returned with an error: %s"), $order->getIncrementId(), $e->getMessage()));
                continue;
            }
        }

        $countNonInvoicedOrder = count($orderIds) - $countInvoicedOrder;

        if ($countNonInvoicedOrder && $countInvoicedOrder) {
            $this->_getSession()->addError(sprintf($this->bamboraHelper->_s("%s order(s) cannot be Invoiced and Captured."), $countNonInvoicedOrder). ' (' .implode(" , ", $notInvoiced) . ')');
        } elseif ($countNonInvoicedOrder) {
            $this->_getSession()->addError($this->bamboraHelper->_s("You cannot Invoice and Capture the order(s)."). ' (' .implode(" , ", $notInvoiced) . ')');
        }

        if ($countInvoicedOrder) {
            $this->_getSession()->addSuccess(sprintf($this->bamboraHelper->_s("You Invoiced and Captured %s order(s)."), $countInvoicedOrder). ' (' .implode(" , ", $invoiced) . ')');
        }

        $this->_redirect('adminhtml/sales_order/index');
    }

    /**
     * Mass Creditmemo and Refund Action
     */
    public function bamboraMassRefundAction()
    {
        $invoiceIds = $this->getRequest()->getPost('invoice_ids', array());
        $countRefundedOrder = 0;
        $refunded = array();
        $notRefunded = array();

        foreach ($invoiceIds as $invoiceId) {
            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = Mage::getModel('sales/order_invoice');
            try {
                $invoice = $invoice->load($invoiceId);

                if (!$invoice->canRefund()) {
                    $notRefunded[] = $invoice->getIncrementId(). '('.$this->bamboraHelper->_s("Creditmemo not available"). ')';
                    continue;
                }

                $order = $invoice->getOrder();

                $pspReference = $order->getPayment()->getAdditionalInformation(Bambora_Online_Model_Checkout_Payment::PSP_REFERENCE);
                if (empty($pspReference)) {
                    $notInvoiced[] = $order->getIncrementId() . '('.$this->bamboraHelper->_s("Bambora transaction not found"). ')';
                    continue;
                }

                $service = Mage::getModel('sales/service_order', $order);
                $creditmemo = $service->prepareInvoiceCreditmemo($invoice);
                $creditmemo->register();
                $creditmemo->save();

                Mage::getModel('core/resource_transaction')
                         ->addObject($creditmemo)
                         ->addObject($creditmemo->getOrder())
                         ->addObject($creditmemo->getInvoice())
                         ->save();

                $countRefundedOrder++;
                $refunded[] = $invoice->getIncrementId();
            } catch (Exception $e) {
                $notInvoiced[] = $invoice->getIncrementId();
                $this->_getSession()->addError(sprintf($this->bamboraHelper->_s("Invoice: %s returned with an error: %s"), $invoice->getIncrementId(), $e->getMessage()));
                continue;
            }
        }

        $countNonRefundedOrder = count($invoiceIds) - $countRefundedOrder;

        if ($countNonRefundedOrder && $countRefundedOrder) {
            $this->_getSession()->addError(sprintf($this->bamboraHelper->_s("%s invoice(s) cannot be Refunded."), $countNonRefundedOrder). ' (' .implode(" , ", $notRefunded) . ')');
        } elseif ($countNonRefundedOrder) {
            $this->_getSession()->addError($this->bamboraHelper->_s("You cannot Refund the invoice(s)."). ' (' .implode(" , ", $notRefunded) . ')');
        }

        if ($countRefundedOrder) {
            $this->_getSession()->addSuccess(sprintf($this->bamboraHelper->_s("You Refunded %s invoice(s)."), $countRefundedOrder). ' (' .implode(" , ", $refunded) . ')');
        }

        $this->_redirect('adminhtml/sales_invoice/index');
    }

    /**
     * Mass Delete Action
     */
    public function bamboraMassDeleteAction()
    {
        $ids = $this->getRequest()->getPost('order_ids', array());
        $countDeleted = 0;
        $deleted = array();
        $notDeleted = array();

        foreach ($ids as $id) {
            $order = Mage::getModel('sales/order');
            ;
            try {
                $order = $order->load($id);
                $order->delete();

                $countDeleted++;
                $deleted[] = $order->getIncrementId();
            } catch (Exception $e) {
                $notDeleted[] = $order->getIncrementId();
                $this->_getSession()->addError(sprintf($this->bamboraHelper->_s("Delete: %s returned with an error: %s"), $order->getIncrementId(), $e->getMessage()));
                continue;
            }
        }

        $countNonDeleted = count($ids) - $countDeleted;

        if ($countNonDeleted && $countDeleted) {
            $this->_getSession()->addError(sprintf($this->bamboraHelper->_s("%s order(s) cannot be Deleted."), $countNonDeleted). ' (' .implode(" , ", $notDeleted) . ')');
        } elseif ($countNonDeleted) {
            $this->_getSession()->addError($this->bamboraHelper->_s("You cannot Delete the order(s)."). ' (' .implode(" , ", $notDeleted) . ')');
        }

        if ($countDeleted) {
            $this->_getSession()->addSuccess(sprintf($this->bamboraHelper->_s("You Deleted %s order(s)."), $countDeleted). ' (' .implode(" , ", $deleted) . ')');
        }

        $this->_redirect('adminhtml/sales_order/index');
    }

    /**
     *
     * @return mixed
     */
    protected function _isAllowed()
    {
        return parent::_isAllowed();
    }
}
