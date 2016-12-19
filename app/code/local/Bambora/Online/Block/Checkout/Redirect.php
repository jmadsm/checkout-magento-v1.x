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
use Bambora_Online_Helper_BamboraConstant as BamboraConstant;

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