<?php

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\SubscriptionInterface;

/**
 * Class SubscriptionResponse
 * @package ReceiptValidator\GooglePlay
 */
class SubscriptionResponse extends AbstractResponse implements SubscriptionInterface
{
    /**
     * @var \Google_Service_AndroidPublisher_SubscriptionPurchase
     */
    protected $response;

    /**
     * @return string
     */
    public function getAutoRenewing()
    {
        return $this->response->autoRenewing;
    }

    /**
     * @return string
     */
    public function getCancelReason()
    {
        return $this->response->cancelReason;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->response->countryCode;
    }

    /**
     * @return string
     */
    public function getPriceAmountMicros()
    {
        return $this->response->priceAmountMicros;
    }

    /**
     * @return string
     */
    public function getPriceCurrencyCode()
    {
        return $this->response->priceCurrencyCode;
    }

    /**
     * @return string
     */
    public function getStartTimeMillis()
    {
        return $this->response->startTimeMillis;
    }

    /**
     * @return string
     */
    public function getExpiresDate()
    {
        return $this->response->expiryTimeMillis;
    }
}
