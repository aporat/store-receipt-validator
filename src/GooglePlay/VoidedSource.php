<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Who initiated a voided purchase (`voidedpurchases` voidedSource).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases#VoidedPurchase
 */
enum VoidedSource: int
{
    case USER      = 0;
    case DEVELOPER = 1;
    case GOOGLE    = 2;
}
