<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The purchase state of a one-time product.
 *
 * `purchases.products` reports it as an integer; `purchases.productsv2` reports the
 * string form, which {@see fromV2String()} maps onto the same cases.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products#ProductPurchase
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.productsv2#PurchaseState
 */
enum ProductPurchaseState: int
{
    case PURCHASED = 0;
    case CANCELED  = 1;
    case PENDING   = 2;

    /**
     * Map the `purchases.productsv2` string state onto this enum, or null for an
     * unknown or missing value.
     */
    public static function fromV2String(?string $value): ?self
    {
        return match ($value) {
            'PURCHASED' => self::PURCHASED,
            'CANCELLED' => self::CANCELED,
            'PENDING'   => self::PENDING,
            default     => null,
        };
    }
}
