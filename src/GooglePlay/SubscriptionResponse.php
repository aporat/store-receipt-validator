<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher\SubscriptionPurchase;

/**
 * Class SubscriptionResponse.
 */
class SubscriptionResponse extends AbstractResponse
{
    /**
     * @var SubscriptionPurchase
     */
    protected $response;

    /**
     * @return bool
     */
    public function getAutoRenewing(): bool
    {
        return $this->response->getAutoRenewing();
    }

    /**
     * @return int|null
     */
    public function getCancelReason(): ?int
    {
        return $this->response->getCancelReason();
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->response->getCountryCode();
    }

    /**
     * @return string
     */
    public function getPriceAmountMicros(): string
    {
        return $this->response->getPriceAmountMicros();
    }

    /**
     * @return string
     */
    public function getPriceCurrencyCode(): string
    {
        return $this->response->getPriceCurrencyCode();
    }

    /**
     * @return string
     */
    public function getStartTimeMillis(): string
    {
        return $this->response->getStartTimeMillis();
    }

    /**
     * @return int
     */
    public function getExpiryTimeMillis(): int
    {
        return $this->response->getExpiryTimeMillis();
    }

    /**
     * @return int|null
     */
    public function getUserCancellationTimeMillis(): ?int
    {
        return $this->response->getUserCancellationTimeMillis();
    }

    /**
     * @return int
     */
    public function getPaymentState(): int
    {
        return $this->response->getPaymentState();
    }

    /**
     * @return string
     *
     * @deprecated Use getExpiryTimeMillis() method instead
     */
    public function getExpiresDate(): string
    {
        return $this->response->expiryTimeMillis;
    }

    /**
     * @return string
     */
    public function getExternalAccountId(): string
    {
        return $this->response->getExternalAccountId();
    }
}
