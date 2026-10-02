<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The state of an order (`orders.get`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders#State
 */
enum OrderState: string
{
    case UNSPECIFIED        = 'STATE_UNSPECIFIED';
    /** Payment not yet complete. */
    case PENDING            = 'PENDING';
    /** Paid and the item granted. */
    case PROCESSED          = 'PROCESSED';
    /** Canceled before completing. */
    case CANCELED           = 'CANCELED';
    /** A refund is in progress. */
    case PENDING_REFUND     = 'PENDING_REFUND';
    case PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';
    case REFUNDED           = 'REFUNDED';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
