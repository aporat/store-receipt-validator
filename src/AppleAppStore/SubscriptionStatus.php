<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * The status of a subscription as returned by the Get All Subscription Statuses endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/status
 */
enum SubscriptionStatus: int
{
    /** The auto-renewable subscription is active. */
    case Active = 1;

    /** The auto-renewable subscription has expired. */
    case Expired = 2;

    /** The auto-renewable subscription is in a billing retry period. */
    case InBillingRetryPeriod = 3;

    /** The auto-renewable subscription is in a Billing Grace Period. */
    case InBillingGracePeriod = 4;

    /** The auto-renewable subscription is revoked. */
    case Revoked = 5;

    /**
     * Returns a human-readable description of this status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active               => 'Active',
            self::Expired              => 'Expired',
            self::InBillingRetryPeriod => 'In Billing Retry Period',
            self::InBillingGracePeriod => 'In Billing Grace Period',
            self::Revoked              => 'Revoked',
        };
    }
}
