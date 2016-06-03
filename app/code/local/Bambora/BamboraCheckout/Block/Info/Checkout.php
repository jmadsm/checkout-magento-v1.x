<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Block_Info_Checkout extends Mage_Payment_Block_Info
{
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) 
        {
            return $this->_paymentSpecificInformation;
        }
        
        $data = array();
      
        $transport = parent::_prepareSpecificInformation($transport);
        
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
?>