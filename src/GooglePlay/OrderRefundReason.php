<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Why an order was refunded (`orderHistory.refundEvent.refundReason`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders#RefundReason
 */
enum OrderRefundReason: string
{
    case UNSPECIFIED = 'REFUND_REASON_UNSPECIFIED';
    case OTHER       = 'OTHER';
    case CHARGEBACK  = 'CHARGEBACK';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
