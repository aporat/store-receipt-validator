<?php

namespace ReceiptValidator\GooglePlay;

/**
 * Class SubscriptionResponse.
 */
class SubscriptionResponse extends AbstractResponse
{
    /**
     * @var \Google_Service_AndroidPublisher_SubscriptionPurchase
     */
    protected $response;

    /**
     * @return bool
     */
    public function getAutoRenewing()
    {
        return (bool) $this->response->getAutoRenewing();
    }

    /**
     * @return int|null
     */
    public function getCancelReason()
    {
        return $this->response->getCancelReason();
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->response->getCountryCode();
    }

    /**
     * @return int
     */
    public function getPriceAmountMicros()
    {
        return $this->response->getPriceAmountMicros();
    }

    /**
     * @return string
     */
    public function getPriceCurrencyCode()
    {
        return $this->response->getPriceCurrencyCode();
    }

    /**
     * @return string
     */
    public function getStartTimeMillis()
    {
        return $this->response->getStartTimeMillis();
    }

    /**
     * @return int
     */
    public function getExpiryTimeMillis()
    {
        return $this->response->getExpiryTimeMillis();
    }

    /**
     * @return int|null
     */
    public function getUserCancellationTimeMillis()
    {
        return $this->response->getUserCancellationTimeMillis();
    }

    /**
     * @return int
     */
    public function getPaymentState()
    {
        return $this->response->getPaymentState();
    }

    /**
     * @return string
     *
     * @deprecated Use getExpiryTimeMillis() method instead
     */
    public function getExpiresDate()
    {
        return $this->response->expiryTimeMillis;
    }
}
