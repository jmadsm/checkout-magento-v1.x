<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Model_BamboraProviders_Checkout extends Bambora_BamboraCheckout_Model_BamboraProviders_Base
{

    public function setBamboraCheckout($bamboracheckoutrequest)
    {   
        $serviceUrl = $this->getEndpoint("checkout").'/checkout' ;

        $jsonData = json_encode($bamboracheckoutrequest);
    
        $expresscheckoutresponse = $this->callRestService($serviceUrl, $jsonData, "POST");

        return $expresscheckoutresponse;
    }



}