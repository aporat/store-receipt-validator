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
    public const int CONSUMPTION_STATE_YET_TO_BE_CONSUMED = 0;
    public const int CONSUMPTION_STATE_CONSUMED = 1;
    public const int PURCHASE_STATE_PURCHASED = 0;
    public const int PURCHASE_STATE_CANCELED = 1;
    public const int ACKNOWLEDGEMENT_STATE_YET_TO_BE = 0;
    public const int ACKNOWLEDGEMENT_STATE_DONE = 1;

    /**
     * @var ProductPurchase|SubscriptionPurchase|SubscriptionPurchaseV2
     */
    protected ProductPurchase|SubscriptionPurchaseV2|SubscriptionPurchase $response;

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
