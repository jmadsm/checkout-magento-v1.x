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
use Bambora_Online_Model_Api_Checkout_Constant_Endpoint as Endpoint;
use Bambora_Online_Model_Api_Checkout_Constant_Model as Model;

class Bambora_Online_Model_Api_Checkout_Checkout extends Bambora_Online_Model_Api_Checkout_Base
{
    /**
     * Create the checkout request
     *
     * @param Bambora_Online_Model_Api_Checkout_Request_Checkout $setcheckoutrequest
     * @param string $apiKey
     * @return Bambora_Online_Model_Api_Checkout_Response_Checkout
     */
    public function setCheckout($setcheckoutrequest, $apiKey)
    {
        try
        {
            $serviceUrl = $this->_getEndpoint(Endpoint::ENDPOINT_CHECKOUT) . '/checkout';
            $jsonData = json_encode($setcheckoutrequest);
            $checkoutResponseJson = $this->_callRestService($serviceUrl, $jsonData, "POST", $apiKey);
            $checkoutResponseArray = json_decode($checkoutResponseJson, true);

            /** @var Bambora_Online_Model_Api_Checkout_Response_Checkout */
            $checkoutResponse = Mage::getModel(Model::RESPONSE_CHECKOUT);
            $checkoutResponse->meta = $this->_mapMeta($checkoutResponseArray);
            $checkoutResponse->token = $checkoutResponseArray['token'];
            $checkoutResponse->url = $checkoutResponseArray['url'];

            return $checkoutResponse;
        }
        catch(Exception $ex)
        {
            $this->logException($ex);
            return null;
        }
    }
}