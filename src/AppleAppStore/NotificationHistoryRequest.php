<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonInterface;

/**
 * Request body for the Get Notification History endpoint.
 *
 * Specifies the date range and optional filters for the notification history query.
 * Use {@see Validator::getNotificationHistory()} to send this request.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/notificationhistoryrequest
 */
final class NotificationHistoryRequest
{
    /**
     * The start date of the timespan for the requested App Store Server Notification history.
     * The earliest supported start date is June 6, 2021.
     */
    public readonly int $startDate;

    /**
     * The end date of the timespan for the requested App Store Server Notification history.
     */
    public readonly int $endDate;

    /**
     * Optional filter: only return notifications of this type.
     *
     * @see ServerNotificationType
     */
    public ?ServerNotificationType $notificationType = null;

    /**
     * Optional filter: only return notifications with this subtype.
     *
     * @see ServerNotificationSubtype
     */
    public ?ServerNotificationSubtype $notificationSubtype = null;

    /**
     * Optional filter: when true, only notifications that your server failed to receive
     * are returned (including those still in the retry queue).
     */
    public ?bool $onlyFailures = null;

    /**
     * Optional filter: only return notifications that contain this transaction ID.
     */
    public ?string $transactionId = null;

    /**
     * @param int|CarbonInterface $startDate  Millisecond timestamp or Carbon instance.
     * @param int|CarbonInterface $endDate    Millisecond timestamp or Carbon instance.
     */
    public function __construct(int|CarbonInterface $startDate, int|CarbonInterface $endDate)
    {
        $this->startDate = $startDate instanceof CarbonInterface
            ? $startDate->getTimestampMs()
            : $startDate;

        $this->endDate = $endDate instanceof CarbonInterface
            ? $endDate->getTimestampMs()
            : $endDate;
    }

    /**
     * Serialize to the JSON request body array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $body = [
            'startDate' => $this->startDate,
            'endDate'   => $this->endDate,
        ];

        if ($this->notificationType !== null) {
            $body['notificationType'] = $this->notificationType->value;
        }

        if ($this->notificationSubtype !== null) {
            $body['notificationSubtype'] = $this->notificationSubtype->value;
        }

        if ($this->onlyFailures !== null) {
            $body['onlyFailures'] = $this->onlyFailures;
        }

        if ($this->transactionId !== null) {
            $body['transactionId'] = $this->transactionId;
        }

        return $body;
    }
}
