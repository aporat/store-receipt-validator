<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher\ProductPurchase;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Google\Service\AndroidPublisher\SubscriptionPurchaseV2;

/**
 * Class AbstractResponse.
 */
abstract class AbstractResponse
{
    const CONSUMPTION_STATE_YET_TO_BE_CONSUMED = 0;
    const CONSUMPTION_STATE_CONSUMED = 1;
    const PURCHASE_STATE_PURCHASED = 0;
    const PURCHASE_STATE_CANCELED = 1;
    const ACKNOWLEDGEMENT_STATE_YET_TO_BE = 0;
    const ACKNOWLEDGEMENT_STATE_DONE = 1;

    /**
     * @var ProductPurchase|SubscriptionPurchase|SubscriptionPurchaseV2
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param ProductPurchase|SubscriptionPurchase|SubscriptionPurchaseV2 $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * @return array|string
     */
    public function getDeveloperPayload(): array|string
    {
        return $this->response->getDeveloperPayload();
    }

    /**
     * @return int
     */
    public function getAcknowledgementState(): int
    {
        return $this->response->acknowledgementState;
    }

    /**
     * @return bool
     */
    public function isAcknowledged()
    {
        return $this->response->acknowledgementState === static::ACKNOWLEDGEMENT_STATE_DONE;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->response->kind;
    }

    /**
     * @return ProductPurchase|SubscriptionPurchase|SubscriptionPurchaseV2
     */
    public function getRawResponse()
    {
        return $this->response;
    }
}
