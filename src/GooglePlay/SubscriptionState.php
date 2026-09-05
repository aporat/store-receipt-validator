<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The state of a subscription purchase as reported by `purchases.subscriptionsv2`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#subscriptionstate
 */
enum SubscriptionState: string
{
    case UNSPECIFIED               = 'SUBSCRIPTION_STATE_UNSPECIFIED';
    case PENDING                   = 'SUBSCRIPTION_STATE_PENDING';
    case ACTIVE                    = 'SUBSCRIPTION_STATE_ACTIVE';
    case PAUSED                    = 'SUBSCRIPTION_STATE_PAUSED';
    case IN_GRACE_PERIOD           = 'SUBSCRIPTION_STATE_IN_GRACE_PERIOD';
    case ON_HOLD                   = 'SUBSCRIPTION_STATE_ON_HOLD';
    case CANCELED                  = 'SUBSCRIPTION_STATE_CANCELED';
    case EXPIRED                   = 'SUBSCRIPTION_STATE_EXPIRED';
    case PENDING_PURCHASE_CANCELED = 'SUBSCRIPTION_STATE_PENDING_PURCHASE_CANCELED';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::UNSPECIFIED;
    }

    /**
     * Whether the user should currently have access to the subscription's benefits.
     *
     * A canceled subscription stays entitled until its expiry time, and a grace period
     * keeps access while Google retries payment. On-hold, paused, pending and expired
     * do not grant access.
     */
    public function isEntitled(): bool
    {
        return match ($this) {
            self::ACTIVE, self::CANCELED, self::IN_GRACE_PERIOD => true,
            default                                              => false,
        };
    }
}
