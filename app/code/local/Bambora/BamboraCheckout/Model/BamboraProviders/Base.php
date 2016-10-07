<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Model_BamboraProviders_Base extends Mage_Core_Model_Abstract
{
    private $endpoints = array(
        "merchant"=>"https://merchant-v1.api.epay.eu",
        "checkout"=>"https://api.v1.checkout.bambora.com",
        "assets"=>"https://v1.checkout.bambora.com/Assets"
    );

    protected function getEndpoint($type)
    {
        return $this->endpoints[$type];
    }

    protected function callRestService($serviceUrl,  $jsonData, $postOrGet)
    {   
        $apiKey = $this->getApiKey();

        $headers = array(
            'Content-Type: application/json',
            'Content-Length: '.strlen(@$jsonData),
            'Accept: application/json',
            'Authorization: '.$apiKey,
            'X-EPay-System: '.this->getModuleHeaderInfo()
        );
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$postOrGet);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        
        $result = curl_exec($curl);
        return $result;        
    }

    private function getApiKey()
    {
        $bambora = Mage::getModel("bamboracheckout/bambora");
        $storeId =  $bambora->getStore()->getStoreId();
        $accesstoken = $bambora->getConfigData('accesstoken', $storeId);
        $merchantNumber = $bambora->getConfigData('merchantnumber', $storeId);
        $secrettoken = $bambora->getConfigData('secrettoken', $storeId);

        $combined = $accesstoken . '@' . $merchantNumber .':'. $secrettoken;
        $encodedKey = base64_encode($combined);
        $apiKey = 'Basic '.$encodedKey;

        return $apiKey;       
    }

    /**
     * Returns the module header
     *
     * @returns string
     */
    private function getModuleHeaderInfo() 
    {
        $bamboraVersion = (string) Mage::getConfig()->getNode()->modules->Bambora_BamboraCheckout->version;
        $magentoVersion = Mage::getVersion();
        $result = 'Magento/' . $magentoVersion . ' Module/' . $bamboraVersion;
    }              

   
}