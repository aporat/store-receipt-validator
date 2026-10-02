<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Whether the user has agreed to a price step-up.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#ConsentState
 */
enum ConsentState: string
{
    case UNSPECIFIED = 'CONSENT_STATE_UNSPECIFIED';
    /** Consent requested but not yet given. */
    case PENDING     = 'PENDING';
    /** The user consented. */
    case CONFIRMED   = 'CONFIRMED';
    /** The new price has been applied. */
    case COMPLETED   = 'COMPLETED';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
