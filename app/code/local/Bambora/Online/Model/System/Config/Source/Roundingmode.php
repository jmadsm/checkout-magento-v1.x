<?php
/**
 * Copyright (c) 2017. All rights reserved ePay A/S (a Bambora Company).
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    ePay A/S (a Bambora Company)
 * @copyright Bambora (http://bambora.com) (http://www.epay.dk)
 * @license   ePay A/S (a Bambora Company)
 *
 */

use Bambora_Online_Helper_BamboraConstant as BamboraConstant;

class Bambora_Online_Model_System_Config_Source_Roundingmode
{
    public function toOptionArray()
    {
        return array(
            array('value'=>BamboraConstant::ROUND_DEFAULT, 'label'=>"Default"),
            array('value'=>BamboraConstant::ROUND_UP, 'label'=>"Always Up"),
            array('value'=>BamboraConstant::ROUND_DOWN, 'label'=>"Always Down")
        );
    }
}
