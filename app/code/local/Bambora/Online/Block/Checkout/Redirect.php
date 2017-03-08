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
        $html .= '<script type="text/javascript">
                    (function (n, t, i, r, u, f, e) { n[u] = n[u] || function() {
                    (n[u].q = n[u].q || []).push(arguments)}; f = t.createElement(i);
                        e = t.getElementsByTagName(i)[0]; f.async = 1; f.src = r; e.parentNode.insertBefore(f, e)
                    })(window, document, "script","'.$data["paymentWindowUrl"].'", "bam");

                      var onClose = function(){
                        window.location.href = "'.$data["cancelUrl"].'";
                      };

                      var options = {
                        "windowstate": 2,
                        "onClose": onClose
                      }

                      bam("open", "'.$data["bamboraCheckoutUrl"].'", options);

                    </script>';

        return $html;
    }
}
