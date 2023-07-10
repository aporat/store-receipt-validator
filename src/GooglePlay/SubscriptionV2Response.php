<?php

namespace ReceiptValidator\GooglePlay;

use Google\Service\AndroidPublisher\SubscriptionPurchaseV2;

/**
 * Class SubscriptionResponse.
 */
class SubscriptionV2Response extends AbstractResponse
{
    /**
     * @var SubscriptionPurchaseV2
     */
    protected $response;
}
