<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The kind of promotion code redeemed at signup (`signupPromotion`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#SignupPromotion
 */
enum SignupPromotionType: string
{
    /** A single-use code. */
    case ONE_TIME_CODE = 'oneTimeCode';
    /** A reusable custom code; the code itself is on the line item. */
    case VANITY_CODE   = 'vanityCode';
}
