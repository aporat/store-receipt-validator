<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Represents the subtype of an App Store Server Notification V2.
 *
 * The subtype provides more specific information about the event that
 * triggered the notification.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/subtype
 */
enum ServerNotificationSubtype: string
{
    // --- Purchase & Subscription Changes ---
    case INITIAL_BUY        = 'INITIAL_BUY';
    case RESUBSCRIBE        = 'RESUBSCRIBE';
    case DOWNGRADE          = 'DOWNGRADE';
    case UPGRADE            = 'UPGRADE';
    case AUTO_RENEW_ENABLED = 'AUTO_RENEW_ENABLED';
    case AUTO_RENEW_DISABLED = 'AUTO_RENEW_DISABLED';
    case VOLUNTARY          = 'VOLUNTARY';
    case SUMMARY            = 'SUMMARY';

    // --- Billing & Renewal Issues ---
    case BILLING_RETRY      = 'BILLING_RETRY';
    case GRACE_PERIOD       = 'GRACE_PERIOD';
    case BILLING_RECOVERY   = 'BILLING_RECOVERY';
    case PRODUCT_NOT_FOR_SALE = 'PRODUCT_NOT_FOR_SALE';

    // --- Price Increases ---
    case PENDING            = 'PENDING';
    case ACCEPTED           = 'ACCEPTED';
    case PRICE_INCREASE     = 'PRICE_INCREASE';

    // --- Refund Reversals ---
    case FAILURE            = 'FAILURE';
    case UNREPORTED         = 'UNREPORTED';

    // --- Fallback ---
    case UNKNOWN            = 'UNKNOWN';

    /**
     * Safer parser that defaults to UNKNOWN for unexpected subtypes.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    /**
     * Check if subtype is related to subscription lifecycle changes.
     */
    public function isSubscriptionChange(): bool
    {
        return in_array($this, [
            self::INITIAL_BUY,
            self::RESUBSCRIBE,
            self::DOWNGRADE,
            self::UPGRADE,
            self::AUTO_RENEW_ENABLED,
            self::AUTO_RENEW_DISABLED,
            self::VOLUNTARY,
        ], true);
    }

    /**
     * Check if subtype is billing or renewal related.
     */
    public function isBillingRelated(): bool
    {
        return in_array($this, [
            self::BILLING_RETRY,
            self::GRACE_PERIOD,
            self::BILLING_RECOVERY,
            self::PRODUCT_NOT_FOR_SALE,
        ], true);
    }

    /**
     * Check if subtype is about price changes.
     */
    public function isPriceChange(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::ACCEPTED,
            self::PRICE_INCREASE,
        ], true);
    }

    /**
     * Check if subtype is refund reversal related.
     */
    public function isRefundReversal(): bool
    {
        return in_array($this, [
            self::FAILURE,
            self::UNREPORTED,
        ], true);
    }
}
