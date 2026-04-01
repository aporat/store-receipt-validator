<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Support\ValueCasting;

/**
 * A single entry in the App Store Server Notification history.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/notificationhistoryresponseitem
 */
final class NotificationHistoryItem
{
    use ValueCasting;

    /**
     * The signed payload of this notification (a JWS string).
     *
     * Decode this with {@see ServerNotification} to access the notification details.
     */
    public readonly ?string $signedPayload;

    /**
     * The result of the App Store server's first attempt to deliver this notification.
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/sendattemptresult
     */
    public readonly ?string $firstSendAttemptResult;

    /**
     * All delivery attempts for this notification, in chronological order.
     *
     * @var list<SendAttemptItem>
     */
    public readonly array $sendAttempts;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->signedPayload          = $this->toString($data, 'signedPayload');
        $this->firstSendAttemptResult = $this->toString($data, 'firstSendAttemptResult');

        $raw      = is_array($data['sendAttempts'] ?? null) ? $data['sendAttempts'] : [];
        $attempts = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $attempts[] = new SendAttemptItem($item);
            }
        }
        $this->sendAttempts = $attempts;
    }

    public function getSignedPayload(): ?string
    {
        return $this->signedPayload;
    }

    public function getFirstSendAttemptResult(): ?string
    {
        return $this->firstSendAttemptResult;
    }

    /**
     * @return list<SendAttemptItem>
     */
    public function getSendAttempts(): array
    {
        return $this->sendAttempts;
    }

    /**
     * Decode and return the notification payload as a typed {@see ServerNotification}.
     *
     * @throws ValidationException If the payload is missing or cannot be decoded.
     */
    public function decodeNotification(): ServerNotification
    {
        if ($this->signedPayload === null || $this->signedPayload === '') {
            throw new ValidationException('signedPayload is missing from this notification history item.');
        }

        return new ServerNotification(['signedPayload' => $this->signedPayload]);
    }

    /**
     * Returns true if the first delivery attempt was successful.
     */
    public function wasDelivered(): bool
    {
        return $this->firstSendAttemptResult === 'SUCCESS';
    }
}
