<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

/**
 * Why a purchase or subscription was cancelled, from the RVS `cancelReason` field.
 *
 * The field is null when the purchase has not been cancelled.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#response-syntax
 */
enum CancelReason: int
{
    /** The reason is not available yet and will be reported later. */
    case UNAVAILABLE = 0;

    /** The customer cancelled the purchase or subscription. */
    case CUSTOMER_CANCELED = 1;

    /**
     * Amazon's system cancelled the purchase, for example after failed billing.
     * Also reported when Amazon customer support cancelled it at the customer's request.
     */
    case SYSTEM_CANCELED = 2;
}
