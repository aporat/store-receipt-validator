<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * How a subscription item is replaced on upgrade or downgrade.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#ReplacementMode
 */
enum ReplacementMode: string
{
    case UNSPECIFIED           = 'REPLACEMENT_MODE_UNSPECIFIED';
    case WITH_TIME_PRORATION   = 'WITH_TIME_PRORATION';
    case CHARGE_PRORATED_PRICE = 'CHARGE_PRORATED_PRICE';
    case WITHOUT_PRORATION     = 'WITHOUT_PRORATION';
    case CHARGE_FULL_PRICE     = 'CHARGE_FULL_PRICE';
    case DEFERRED              = 'DEFERRED';
    case KEEP_EXISTING         = 'KEEP_EXISTING';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
