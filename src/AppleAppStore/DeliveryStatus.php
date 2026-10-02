<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

/**
 * Whether the app successfully delivered an in-app purchase that works properly.
 *
 * Used by the v2 Send Consumption Information endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/deliverystatus
 */
enum DeliveryStatus: string
{
    /** The app delivered the In-App Purchase and it's working properly. */
    case DELIVERED = 'DELIVERED';

    /** The app didn't deliver the In-App Purchase due to a quality issue. */
    case UNDELIVERED_QUALITY_ISSUE = 'UNDELIVERED_QUALITY_ISSUE';

    /** The app delivered the wrong item. */
    case UNDELIVERED_WRONG_ITEM = 'UNDELIVERED_WRONG_ITEM';

    /** The app didn't deliver the In-App Purchase due to a server outage. */
    case UNDELIVERED_SERVER_OUTAGE = 'UNDELIVERED_SERVER_OUTAGE';

    /** The app didn't deliver the In-App Purchase for other reasons. */
    case UNDELIVERED_OTHER = 'UNDELIVERED_OTHER';

    /**
     * Map a legacy v1 integer delivery status to its v2 equivalent.
     *
     * v1 values: 0 delivered and working, 1 quality issue, 2 wrong item,
     * 3 server outage, 4 in-game currency change, 5 other reason.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/deliverystatusv1
     */
    public static function fromLegacyValue(int $value): self
    {
        return match ($value) {
            0       => self::DELIVERED,
            1       => self::UNDELIVERED_QUALITY_ISSUE,
            2       => self::UNDELIVERED_WRONG_ITEM,
            3       => self::UNDELIVERED_SERVER_OUTAGE,
            4, 5    => self::UNDELIVERED_OTHER,
            default => throw new \ValueError(sprintf('Unknown legacy delivery status %d.', $value)),
        };
    }
}
