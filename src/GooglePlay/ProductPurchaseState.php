<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The purchase state of a one-time product (`purchases.products` purchaseState).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products#ProductPurchase
 */
enum ProductPurchaseState: int
{
    case PURCHASED = 0;
    case CANCELED  = 1;
    case PENDING   = 2;
}
