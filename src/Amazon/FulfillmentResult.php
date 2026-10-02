<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

/**
 * The fulfillment status an app reported for a subscription purchase, from the RVS
 * `fulfillmentResult` field. Null until the app acknowledges fulfillment, and always
 * null for consumables and entitlements.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 */
enum FulfillmentResult: string
{
    /** The app delivered the content and the customer consumed it. */
    case FULFILLED = 'FULFILLED';

    /** The customer already owned the content. */
    case EXISTING_PURCHASE = 'EXISTING_PURCHASE';

    /** The customer was not eligible for the content. */
    case NOT_ELIGIBLE = 'NOT_ELIGIBLE';

    /** The app was unable to deliver the content. */
    case UNAVAILABLE = 'UNAVAILABLE';
}
