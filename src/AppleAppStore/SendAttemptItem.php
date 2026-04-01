<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonImmutable;
use ReceiptValidator\Support\ValueCasting;

/**
 * A single delivery attempt for an App Store Server Notification.
 *
 * Appears in {@see CheckTestNotificationResponse::$sendAttempts} and
 * {@see NotificationHistoryItem::$sendAttempts}.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/sendattemptitem
 */
final class SendAttemptItem
{
    use ValueCasting;

    /**
     * The date the App Store attempted to deliver the notification.
     */
    public readonly ?CarbonImmutable $attemptDate;

    /**
     * The result of the App Store server's most recent attempt to send the notification.
     *
     * Common values: "SUCCESS", "TIMED_OUT", "TLS_ISSUE", "CIRCULAR_REDIRECT",
     * "NO_RESPONSE", "SOCKET_TIMEOUT", "UNSUCCESSFUL_HTTP_RESPONSE_CODE",
     * "INVALID_RESPONSE", "OTHER".
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/sendattemptresult
     */
    public readonly ?string $sendAttemptResult;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->attemptDate       = $this->toDateFromMs($data, 'attemptDate');
        $this->sendAttemptResult = $this->toString($data, 'sendAttemptResult');
    }

    public function getAttemptDate(): ?CarbonImmutable
    {
        return $this->attemptDate;
    }

    public function getSendAttemptResult(): ?string
    {
        return $this->sendAttemptResult;
    }

    public function isSuccessful(): bool
    {
        return $this->sendAttemptResult === 'SUCCESS';
    }
}
