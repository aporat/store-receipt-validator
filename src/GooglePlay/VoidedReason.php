<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * Why a purchase was voided (`voidedpurchases` voidedReason).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases#VoidedPurchase
 */
enum VoidedReason: int
{
    case OTHER                  = 0;
    case REMORSE                = 1;
    case NOT_RECEIVED           = 2;
    case DEFECTIVE              = 3;
    case ACCIDENTAL_PURCHASE    = 4;
    case FRAUD                  = 5;
    case FRIENDLY_FRAUD         = 6;
    case CHARGEBACK             = 7;
    case UNACKNOWLEDGED_PURCHASE = 8;
}
