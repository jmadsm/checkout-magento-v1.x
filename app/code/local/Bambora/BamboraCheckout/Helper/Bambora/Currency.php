<?php
/**
 * Copyright Bambora | Checkout, (c) 2016.
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software. 
 * It is also not legal to do any changes to the software and distribute it in your own name / brand. 
 */
class Bambora_BamboraCheckout_Helper_Bambora_Currency extends Mage_Core_Helper_Abstract
{
    public function convertPriceToMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if($minorUnits == "" || $minorUnits == null)
            $minorUnits = $defaultMinorUnits; 

        if($amount == "" || $amount == null)
            return 0;

        return round($amount,$minorUnits) * pow(10,$minorUnits);
    }

    public function convertPriceFromMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if($minorUnits == "" || $minorUnits == null)
            $minorUnits = $defaultMinorUnits;
        
        if($amount == "" || $amount == null)
            return 0;

        return number_format($amount / pow(10,$minorUnits),$minorUnits);
    }

    public function getCurrencyMinorunits($currencyCode)
    {
        switch($currencyCode)
        {
            case "TTD":
            case "KMF":
            case "ADP":
            case "TPE":
            case "BIF":
            case "DJF":
            case "MGF":
            case "XPF":
            case "GNF":
            case "BYR":
            case "PYG":
            case "JPY":
            case "CLP":
            case "XAF":
            case "TRL":
            case "VUV":
            case "CLF":
            case "KRW":
            case "XOF":
            case "RWF":
                return 0;

            case "IQD":
            case "TND":
            case "BHD":
            case "JOD":
            case "OMR":
            case "KWD":
            case "LYD":
                return 3;

            default:
                return 2;
        }
        
    }
}

