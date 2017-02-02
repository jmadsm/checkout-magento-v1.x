<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 */
class Bambora_Online_Model_System_Config_Source_Windowstate
{
    public function toOptionArray()
    {
        return array(
            array('value'=>2, 'label'=>Mage::helper('adminhtml')->__('Overlay')),
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Full Screen')),
        );
    }
}
