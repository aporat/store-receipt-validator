<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class PurchaseResponse
 * @package ReceiptValidator\GooglePlay
 */
class PurchaseResponse extends AbstractResponse
{
    /**
     * @var \Google_Service_AndroidPublisher_ProductPurchase
     */
    protected $response;

    protected $developerPayload = [];

    public function __construct($response)
    {
        parent::__construct($response);
        $this->developerPayload = json_decode($this->response->developerPayload, true);
    }

    /**
     * @return int
     */
    public function getConsumptionState()
    {
        return $this->response->consumptionState;
    }

    /**
     * @return string
     */
    public function getPurchaseTimeMillis()
    {
        return $this->response->purchaseTimeMillis;
    }

    public function getDeveloperPayload()
    {
        return $this->developerPayload;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getDeveloperPayloadElement($key)
    {
        return (isset($this->developerPayload[$key])) ? $this->developerPayload[$key] : '';
    }

    /**
     * @return string
     */
    public function getPurchaseState()
    {
        return $this->response->purchaseState;
    }
}
