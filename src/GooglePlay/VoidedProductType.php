<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * `voidedPurchaseNotification.productType` values from a Real-time Developer Notification.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#voided-purchase
 */
enum VoidedProductType: int
{
    case UNKNOWN      = 0;
    case SUBSCRIPTION = 1;
    case ONE_TIME     = 2;

    public static function fromInt(int $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }
}
