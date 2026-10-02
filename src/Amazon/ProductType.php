<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

/**
 * The type of product a Receipt Verification Service (RVS) receipt was issued for.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 */
enum ProductType: string
{
    /** A product that can be bought repeatedly, such as in-game currency. */
    case CONSUMABLE = 'CONSUMABLE';

    /** A one-time purchase that permanently unlocks content. */
    case ENTITLED = 'ENTITLED';

    /** A recurring subscription. */
    case SUBSCRIPTION = 'SUBSCRIPTION';

    public function isConsumable(): bool
    {
        return $this === self::CONSUMABLE;
    }

    public function isEntitlement(): bool
    {
        return $this === self::ENTITLED;
    }

    public function isSubscription(): bool
    {
        return $this === self::SUBSCRIPTION;
    }
}
