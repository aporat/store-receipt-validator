<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher\ProductPurchase;

/**
 * Class PurchaseResponse.
 */
class PurchaseResponse extends AbstractResponse
{
    /**
     * @var ProductPurchase
     */
    protected $response;

    protected mixed $developerPayload = [];

    public function __construct($response)
    {
        parent::__construct($response);
        if (isset($this->response->developerPayload)) {
            $this->developerPayload = json_decode($this->response->developerPayload, true);
        }
    }

    /**
     * @return int
     */
    public function getConsumptionState(): int
    {
        return $this->response->consumptionState;
    }

    /**
     * @return string
     */
    public function getPurchaseTimeMillis(): string
    {
        return $this->response->purchaseTimeMillis;
    }

    public function getDeveloperPayload(): array|string
    {
        return $this->developerPayload;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function getDeveloperPayloadElement(string $key): string
    {
        return (isset($this->developerPayload[$key])) ? $this->developerPayload[$key] : '';
    }

    /**
     * @return string
     */
    public function getPurchaseState(): string
    {
        return $this->response->purchaseState;
    }
}
