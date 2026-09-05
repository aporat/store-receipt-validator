<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * `voidedPurchaseNotification.refundType` values from a Real-time Developer Notification.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#voided-purchase
 */
enum RefundType: int
{
    case UNKNOWN                = 0;
    case FULL                   = 1;
    case QUANTITY_BASED_PARTIAL = 2;

    public static function fromInt(int $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }
}
