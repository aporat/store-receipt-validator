<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The consumption state of a one-time product.
 *
 * `purchases.products` reports it as an integer; `purchases.productsv2` reports the
 * string form per line item, which {@see fromV2String()} maps onto the same cases.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products#ProductPurchase
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.productsv2#ConsumptionState
 */
enum ConsumptionState: int
{
    case YET_TO_BE_CONSUMED = 0;
    case CONSUMED           = 1;

    /**
     * Map the `purchases.productsv2` string state onto this enum, or null for an
     * unknown or missing value.
     */
    public static function fromV2String(?string $value): ?self
    {
        return match ($value) {
            'CONSUMPTION_STATE_YET_TO_BE_CONSUMED' => self::YET_TO_BE_CONSUMED,
            'CONSUMPTION_STATE_CONSUMED'           => self::CONSUMED,
            default                                => null,
        };
    }
}
