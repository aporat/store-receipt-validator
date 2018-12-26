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
     * Constructor
     *
     * @param \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return array|string
     */
    public function getDeveloperPayload()
    {
        return $this->response->getDeveloperPayload();
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->response->kind;
    }

    /**
     * @return \Google_Service_AndroidPublisher_ProductPurchase|\Google_Service_AndroidPublisher_SubscriptionPurchase
     */
    public function getRawResponse()
    {
        return $this->response;
    }
}
