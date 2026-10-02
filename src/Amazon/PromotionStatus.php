<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

/**
 * Where a promotion stands for the customer on a subscription receipt.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#promotions
 */
enum PromotionStatus: string
{
    /** The promotion is scheduled and has not started yet. */
    case QUEUED = 'Queued';

    /** The customer is currently benefiting from the promotion. */
    case IN_PROGRESS = 'InProgress';

    /** The promotion has ended. */
    case COMPLETED = 'Completed';

    public function isActive(): bool
    {
        return $this === self::IN_PROGRESS;
    }
}
