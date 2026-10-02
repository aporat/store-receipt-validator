<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

/**
 * The kind of promotional pricing attached to a subscription receipt.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#promotions
 */
enum PromotionType: string
{
    /** An introductory price offered to all new subscribers. */
    case INTRODUCTORY_PRICE = 'Introductory Price - All Customers';

    /** A promotional price offered to customers whose subscription lapsed. */
    case PROMOTIONAL_PRICE_LAPSED_CUSTOMERS = 'Promotional Price - Lapsed Customers';

    /** A discount offered to keep a customer who was about to cancel. */
    case RETENTION_OFFER = 'Retention Offer';
}
