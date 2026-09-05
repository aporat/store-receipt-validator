<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Whether a purchase has been acknowledged by the developer.
 *
 * Google refunds unacknowledged purchases after three days, so acknowledgement
 * (client-side or via {@see Validator::acknowledgeSubscription()} /
 * {@see Validator::acknowledgeProduct()}) is required.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#acknowledgementstate
 */
enum AcknowledgementState: string
{
    case UNSPECIFIED  = 'ACKNOWLEDGEMENT_STATE_UNSPECIFIED';
    case PENDING      = 'ACKNOWLEDGEMENT_STATE_PENDING';
    case ACKNOWLEDGED = 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::UNSPECIFIED;
    }

    /**
     * Map the integer acknowledgementState used by `purchases.products` (0 = pending,
     * 1 = acknowledged) onto this enum.
     */
    public static function fromProductState(?int $state): self
    {
        return match ($state) {
            0       => self::PENDING,
            1       => self::ACKNOWLEDGED,
            default => self::UNSPECIFIED,
        };
    }
}
