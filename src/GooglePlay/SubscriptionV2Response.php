<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher\ProductPurchase;
use Google\Service\AndroidPublisher\SubscriptionPurchase;
use Google\Service\AndroidPublisher\SubscriptionPurchaseV2;

/**
 * Class SubscriptionResponse.
 */
class SubscriptionV2Response extends AbstractResponse
{
    /**
     * @var ProductPurchase|SubscriptionPurchaseV2|SubscriptionPurchase
     */
    protected ProductPurchase|SubscriptionPurchaseV2|SubscriptionPurchase $response;
}
