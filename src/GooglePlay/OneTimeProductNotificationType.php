<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * `oneTimeProductNotification.notificationType` values from a Real-time Developer Notification.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#one-time
 */
enum OneTimeProductNotificationType: int
{
    /** Fallback for values this library does not know about yet. Google never sends 0. */
    case UNKNOWN   = 0;

    case PURCHASED = 1;
    case CANCELED  = 2;

    /**
     * Safer version of from() that falls back to UNKNOWN on unknown values.
     */
    public static function fromInt(int $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }
}
