<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The consumption state of a one-time product (`purchases.products` consumptionState).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.products#ProductPurchase
 */
enum ConsumptionState: int
{
    case YET_TO_BE_CONSUMED = 0;
    case CONSUMED           = 1;
}
