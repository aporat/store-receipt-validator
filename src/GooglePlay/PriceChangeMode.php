<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The direction of a pending or applied subscription price change.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#PriceChangeMode
 */
enum PriceChangeMode: string
{
    case UNSPECIFIED            = 'PRICE_CHANGE_MODE_UNSPECIFIED';
    case PRICE_DECREASE         = 'PRICE_DECREASE';
    /** An opt-in price increase the user must accept. */
    case PRICE_INCREASE         = 'PRICE_INCREASE';
    /** A price increase that applies unless the user cancels. */
    case OPT_OUT_PRICE_INCREASE = 'OPT_OUT_PRICE_INCREASE';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
