<?php

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Exceptions\ValidationException;

/**
 * Represents notification types for App Store Server Notifications V2.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/notificationtype
 */
enum ServerNotificationType: string
{
    // --- Subscription Lifecycle Events ---

    case SUBSCRIBED               = 'SUBSCRIBED';
    case DID_RENEW                = 'DID_RENEW';
    case DID_CHANGE_RENEWAL_PREF  = 'DID_CHANGE_RENEWAL_PREF';
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';
    case EXPIRED                  = 'EXPIRED';
    case DID_FAIL_TO_RENEW        = 'DID_FAIL_TO_RENEW';
    case GRACE_PERIOD_EXPIRED     = 'GRACE_PERIOD_EXPIRED';
    case RENEWAL_EXTENDED         = 'RENEWAL_EXTENDED';

    case RENEWAL_EXTENSION        = 'RENEWAL_EXTENSION';

    // --- Offer and Price Change Events ---

    case OFFER_REDEEMED           = 'OFFER_REDEEMED';
    case PRICE_INCREASE           = 'PRICE_INCREASE';

    // --- Refund and Revocation Events ---

    case REFUND                   = 'REFUND';
    case REFUND_DECLINED          = 'REFUND_DECLINED';
    case REFUND_REVERSED          = 'REFUND_REVERSED';
    case REVOKE                   = 'REVOKE';

    // --- Consumable and Other Purchase Events ---

    case CONSUMPTION_REQUEST      = 'CONSUMPTION_REQUEST';
    case ONE_TIME_CHARGE          = 'ONE_TIME_CHARGE';
    case EXTERNAL_PURCHASE_TOKEN  = 'EXTERNAL_PURCHASE_TOKEN';

    // --- Testing ---

    case TEST                     = 'TEST';

    // --- Fallback ---

    /**
     * Represents an unknown or future notification type not yet handled by this library.
     */
    case UNKNOWN                  = 'UNKNOWN';

    /**
     * Safer version of from() that falls back to UNKNOWN on unknown values.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    /**
     * Whether this notification relates to subscription lifecycle events.
     */
    public function isSubscriptionLifecycle(): bool
    {
        return in_array($this, [
            self::SUBSCRIBED,
            self::DID_RENEW,
            self::DID_CHANGE_RENEWAL_PREF,
            self::DID_CHANGE_RENEWAL_STATUS,
            self::EXPIRED,
            self::DID_FAIL_TO_RENEW,
            self::GRACE_PERIOD_EXPIRED,
            self::RENEWAL_EXTENDED,
            self::RENEWAL_EXTENSION,
        ], true);
    }

    /**
     * Whether this notification relates to a refund or revocation event.
     */
    public function isRefundRelated(): bool
    {
        return in_array($this, [
            self::REFUND,
            self::REFUND_DECLINED,
            self::REFUND_REVERSED,
            self::REVOKE,
        ], true);
    }

    /**
     * Whether this notification relates to offers or price events.
     */
    public function isOfferOrPriceEvent(): bool
    {
        return in_array($this, [
            self::OFFER_REDEEMED,
            self::PRICE_INCREASE,
        ], true);
    }
}
