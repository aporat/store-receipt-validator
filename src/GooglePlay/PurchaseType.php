<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * How a one-time product was obtained (`purchases.products` purchaseType).
 *
 * Only present for purchases that were not made with a standard in-app billing flow.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products#ProductPurchase
 */
enum PurchaseType: int
{
    /** Purchased from a licence-testing account. */
    case TEST     = 0;
    /** Purchased with a promo code. */
    case PROMO    = 1;
    /** Purchased by watching a rewarded ad. */
    case REWARDED = 2;
}
