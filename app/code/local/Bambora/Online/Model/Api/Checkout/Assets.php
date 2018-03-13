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
use Bambora_Online_Model_Api_Checkout_Constant_Endpoint as Endpoint;

class Bambora_Online_Model_Api_Checkout_Assets extends Bambora_Online_Model_Api_Checkout_Base
{
    /**
     * Get Checkout payment window js url
     *
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $url = $this->_getEndpoint(Endpoint::ENDPOINT_GLOBAL_ASSETS).'/bambora_icon_64x64.png';

        return $url;
    }
}
