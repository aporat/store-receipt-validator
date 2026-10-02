<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Your preferred outcome for a refund request.
 *
 * Used by the v2 Send Consumption Information endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/refundpreference
 */
enum RefundPreference: string
{
    /** You prefer that the App Store declines the refund. */
    case DECLINE = 'DECLINE';

    /** You prefer that the App Store grants the refund in full. */
    case GRANT_FULL = 'GRANT_FULL';

    /**
     * You prefer that the App Store grants a prorated refund.
     *
     * Requires a consumptionPercentage greater than 0 and less than 100000, and
     * is not supported for auto-renewable subscriptions.
     */
    case GRANT_PRORATED = 'GRANT_PRORATED';

    /**
     * Map a legacy v1 integer refund preference to its v2 equivalent.
     *
     * v1 values: 0 undeclared, 1 prefer grant, 2 prefer decline, 3 no preference.
     * Undeclared and no preference have no v2 equivalent and return null, which
     * omits the field from the request.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/refundpreferencev1
     */
    public static function fromLegacyValue(int $value): ?self
    {
        return match ($value) {
            0, 3    => null,
            1       => self::GRANT_FULL,
            2       => self::DECLINE,
            default => throw new \ValueError(sprintf('Unknown legacy refund preference %d.', $value)),
        };
    }
}
