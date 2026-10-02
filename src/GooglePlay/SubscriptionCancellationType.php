<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * How a subscription is canceled via `purchases.subscriptionsv2.cancel`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/cancel#CancellationType
 */
enum SubscriptionCancellationType: string
{
    /** Stops the next renewal on the user's behalf; the user can still restore the subscription. */
    case USER_REQUESTED_STOP_RENEWALS = 'USER_REQUESTED_STOP_RENEWALS';

    /** Stops the next payment; the subscription cannot be restored. */
    case DEVELOPER_REQUESTED_STOP_PAYMENTS = 'DEVELOPER_REQUESTED_STOP_PAYMENTS';
}
