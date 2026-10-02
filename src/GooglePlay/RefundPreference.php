<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The developer's recommendation when reviewing a chargeback via `orders.reviewrefund`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders/reviewrefund
 */
enum RefundPreference: string
{
    case DECLINE = 'DECLINE';
    case APPROVE = 'APPROVE';
    case NEUTRAL = 'NEUTRAL';
}
