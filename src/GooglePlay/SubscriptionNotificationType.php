<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * `subscriptionNotification.notificationType` values from a Real-time Developer Notification.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#sub
 */
enum SubscriptionNotificationType: int
{
    /** Fallback for values this library does not know about yet. Google never sends 0. */
    case UNKNOWN                   = 0;

    case RECOVERED                 = 1;
    case RENEWED                   = 2;
    case CANCELED                  = 3;
    case PURCHASED                 = 4;
    case ON_HOLD                   = 5;
    case IN_GRACE_PERIOD           = 6;
    case RESTARTED                 = 7;
    case PRICE_CHANGE_CONFIRMED    = 8;
    case DEFERRED                  = 9;
    case PAUSED                    = 10;
    case PAUSE_SCHEDULE_CHANGED    = 11;
    case REVOKED                   = 12;
    case EXPIRED                   = 13;
    case PRICE_CHANGE_UPDATED      = 19;
    case PENDING_PURCHASE_CANCELED = 20;

    /**
     * Safer version of from() that falls back to UNKNOWN on unknown values.
     */
    public static function fromInt(int $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    /**
     * Whether Play has taken the entitlement away immediately rather than letting it
     * run to its expiry. A cancel is *not* one of these: the user keeps access until
     * the current period ends.
     */
    public function revokesEntitlement(): bool
    {
        return $this === self::REVOKED;
    }
}
