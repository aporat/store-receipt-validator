<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The `type` filter for `purchases.voidedpurchases.list`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases/list
 */
enum VoidedPurchaseType: int
{
    /** Only voided one-time product purchases (the API default). */
    case ONE_TIME_ONLY         = 0;
    /** Voided one-time products and voided subscriptions. */
    case INCLUDE_SUBSCRIPTIONS = 1;
}
