<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Where a subscription price change is in its lifecycle.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#PriceChangeState
 */
enum PriceChangeState: string
{
    case UNSPECIFIED = 'PRICE_CHANGE_STATE_UNSPECIFIED';
    /** Waiting for the user to accept. */
    case OUTSTANDING = 'OUTSTANDING';
    /** Accepted (or opt-out) and will apply at the next renewal. */
    case CONFIRMED   = 'CONFIRMED';
    /** Already charged at the new price. */
    case APPLIED     = 'APPLIED';
    /** The price change was withdrawn. */
    case CANCELED    = 'CANCELED';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
