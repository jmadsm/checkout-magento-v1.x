<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Model_BamboraProviders_Merchant extends Bambora_BamboraCheckout_Model_BamboraProviders_Base
{
    public function getPaymentTypes($currency, $amount)
    {   
        $serviceUrl = $this->getEndpoint("merchant").'/paymenttypes?currency='. $currency .'&amount='.$amount;
        $data = array();
        
        $jsonData = json_encode($data);
        
        $result = $this->callRestService($serviceUrl, $jsonData, "GET");
        return $result;        
    }

    public function gettransactionInformation($transactionid)
	{            
        $serviceUrl = $this->getEndpoint("merchant").'/transactions/'. sprintf('%.0F',$transactionid);                         

        $data = array();    
        $jsonData = json_encode($data);
        
        $result = $this->callRestService($serviceUrl, $jsonData, "GET");
        return $result;    
	}


}