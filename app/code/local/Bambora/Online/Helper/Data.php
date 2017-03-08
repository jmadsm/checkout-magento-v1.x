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
class Bambora_Online_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Return if Checkout Api Result is valid
     *
     * @param Bambora_Online_Model_Api_Checkout_Response_Base $request
     * @param mixed $id
     * @param bool $isBackoffice
     * @param string &$message
     * @return bool
     */
    public function validateCheckoutApiResult($response, $id, $isBackoffice, &$message)
    {
        if (!isset($response) || $response === false || !isset($response->meta)) {
            //Error without description
            $message = $this->_s("No answer from Bambora");
            $this->log($id, $message, Zend_Log::ERR);
            return false;
        } elseif (!$response->meta->result) {
            // Error with description
            $message = $isBackoffice ? $response->meta->message->merchant : $response->meta->message->enduser;
            $logMessage = isset($response->meta->message->merchant) ? $response->meta->message->merchant : $response->meta->message->enduser;
            $this->log($id, $logMessage, Zend_Log::ERR);
            return false;
        }
        return true;
    }

    /**
     * Write exception to log
     *
     * @param string $message
     * @param int $level
     * @return void
     */
    public function log($id, $message, $level = null)
    {
        $errorMessage = sprintf("(ID: %s) - %s ", $id, $message);
        Mage::log($errorMessage, $level, 'bambora.log');
    }

    /**
     * Write exception to log
     *
     * @param Exception $exception
     * @return void
     */
    public function logException($exception)
    {
        Mage::log($exception->__toString(), 2, 'bamboraException.log');
    }


    /**
     * Convert an amount to minorunits
     *
     * @param $amount
     * @param $minorUnits
     * @param $defaultMinorUnits = 2
     * @return int
     */
    public function convertPriceToMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if ($minorUnits == "" || $minorUnits == null) {
            $minorUnits = $defaultMinorUnits;
        }

        if ($amount == "" || $amount == null) {
            return 0;
        }

        return $amount * pow(10, $minorUnits);
        ;
    }

    /**
     * Convert an amount from minorunits
     *
     * @param $amount
     * @param $minorUnits
     * @param $defaultMinorUnits = 2
     * @return string
     */
    public function convertPriceFromMinorUnits($amount, $minorUnits, $defaultMinorUnits = 2)
    {
        if ($minorUnits == "" || $minorUnits == null) {
            $minorUnits = $defaultMinorUnits;
        }

        if ($amount == "" || $amount == null) {
            return 0;
        }

        return number_format($amount / pow(10, $minorUnits), $minorUnits);
    }

    /**
     * Return minorunits based on Currency Code
     *
     * @param $currencyCode
     * @return int
     */
    public function getCurrencyMinorunits($currencyCode)
    {
        $currencyArray = array(
        'TTD' => 0, 'KMF' => 0, 'ADP' => 0, 'TPE' => 0, 'BIF' => 0,
        'DJF' => 0, 'MGF' => 0, 'XPF' => 0, 'GNF' => 0, 'BYR' => 0,
        'PYG' => 0, 'JPY' => 0, 'CLP' => 0, 'XAF' => 0, 'TRL' => 0,
        'VUV' => 0, 'CLF' => 0, 'KRW' => 0, 'XOF' => 0, 'RWF' => 0,
        'IQD' => 3, 'TND' => 3, 'BHD' => 3, 'JOD' => 3, 'OMR' => 3,
        'KWD' => 3, 'LYD' => 3);

        return key_exists($currencyCode, $currencyArray) ? $currencyArray[$currencyCode] : 2;
    }

    public function getShopLocalCode()
    {
        $localCode =  Mage::app()->getLocale()->getLocaleCode();
        return str_replace('_', '-', $localCode);
    }

    /**
     * Returns information about magento and module version
     *
     * @return string
     */
    public function getCmsInfo()
    {
        $bamboraVersion = (string) Mage::getConfig()->getNode()->modules->Mage_Epay->version;
        $magentoVersion = Mage::getVersion();
        $result = 'Magento/' . $magentoVersion . ' Module/' . $bamboraVersion;

        return $result;
    }

    /**
     * Translate the text and return a string
     *
     * @param string $text
     * @return string
     */
    public function _s($text)
    {
        return $this->__($text);
    }
}
