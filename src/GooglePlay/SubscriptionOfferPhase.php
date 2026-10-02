<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Which phase of its offer a subscription line item is in (`offerPhase`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#OfferPhase
 */
enum SubscriptionOfferPhase: string
{
    /** Phase unknown or not reported. */
    case UNSPECIFIED        = 'OFFER_PHASE_UNSPECIFIED';
    /** The user is in a free trial. */
    case FREE_TRIAL         = 'FREE_TRIAL';
    /** The user is paying an introductory price. */
    case INTRODUCTORY_PRICE = 'INTRODUCTORY_PRICE';
    /** The user is paying the base plan price. */
    case BASE_PRICE         = 'BASE_PRICE';
    /** A proration period after a plan change; see the original phase on the line item. */
    case PRORATION_PERIOD   = 'PRORATION_PERIOD';

    /**
     * Safer version of from() that falls back to UNSPECIFIED on unknown values.
     */
    public static function fromString(?string $value): self
    {
        return $value !== null ? (self::tryFrom($value) ?? self::UNSPECIFIED) : self::UNSPECIFIED;
    }
}
