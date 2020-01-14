<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay\Exception;

use ReceiptValidator\RunTimeException;

/**
 * @author Florent Blaison
 */
class AlreadyAcknowledgeException extends RunTimeException
{
    public static function fromSubscriptionPurchase(
        \Google_Service_AndroidPublisher_SubscriptionPurchase $subscriptionPurchase
    ) {
        return new static(
            \sprintf('Subscription purchase %s already acknowledged', $subscriptionPurchase->getOrderId())
        );
    }

    public static function fromProductPurchase(
        \Google_Service_AndroidPublisher_ProductPurchase $productPurchase
    ) {
        return new static(
            \sprintf('Product purchase %s already acknowledged', $productPurchase->getOrderId())
        );
    }
}
