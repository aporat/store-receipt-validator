<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class AbstractResponse
 * @package ReceiptValidator\GooglePlay
 */
abstract class AbstractResponse
{
    const CONSUMPTION_STATE_YET_TO_BE_CONSUMED = 0;
    const CONSUMPTION_STATE_CONSUMED = 1;
    const PURCHASE_STATE_PURCHASED = 0;
    const PURCHASE_STATE_CANCELED = 1;

    /**
     * @var \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase
     */
    protected $response;

    /**
     * @var array
     */
    protected $developerPayload = array();

    /**
     * Constructor
     *
     * @param \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase $response
     */
    public function __construct($response)
    {
        $this->response = $response;
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
     * @return array
     */
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
    public function getKind()
    {
        return $this->response->kind;
    }

    /**
     * @return string
     */
    public function getPurchaseState()
    {
        return $this->response->purchaseState;
    }
}
