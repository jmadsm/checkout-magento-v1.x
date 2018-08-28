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
class Bambora_Online_Block_Checkout_Redirect extends Mage_Core_Block_Template
{
    /**
     * {@inheritdoc}
     */
    public function _toHtml()
    {
        $data = $this->getData();

        $html = '<h2 class="bambora_redirect">'.$data["headerText"].'</h2>';
        $html .= '<h3 class="bambora_redirect">'.$data["headerText2"].'</h3>';
        $html .= '<script src="https://static.bambora.com/checkout-sdk-web/latest/checkout-sdk-web.min.js"></script>';
        $html .= '<script type="text/javascript">
                   var checkoutToken = "'.$data["checkoutToken"].'";
                    if('.$data["windowState"].' === 1){
                        new Bambora.RedirectCheckout(checkoutToken);
                    } else {
                        var checkout = new Bambora.ModalCheckout(null);

                        checkout.on(Bambora.Event.Cancel, function(payload){
                            window.location.href = payload.declineUrl;
                        });

                        checkout.on(Bambora.Event.Close, function(payload){
                            window.location.href = payload.acceptUrl;
                        });

                        checkout.initialize(checkoutToken).then(function() {
                            checkout.show();
                        });                     
                    }
                    </script>';
        return $html;
    }
}
