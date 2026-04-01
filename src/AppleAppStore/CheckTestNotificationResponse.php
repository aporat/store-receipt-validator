<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Support\ValueCasting;

/**
 * Response from the Get Test Notification Status endpoint.
 *
 * Returns the delivery status of a test notification that was previously
 * requested via {@see Validator::requestTestNotification()}.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/checktestnotificationresponse
 */
final class CheckTestNotificationResponse
{
    use ValueCasting;

    /**
     * The signed payload of the test notification (a JWS string).
     *
     * Decode this with {@see ServerNotification} to access the notification details.
     */
    public readonly ?string $signedPayload;

    /**
     * The result of the App Store server's first attempt to deliver the test notification.
     *
     * Common values: "SUCCESS", "TIMED_OUT", "TLS_ISSUE", "CIRCULAR_REDIRECT",
     * "NO_RESPONSE", "SOCKET_TIMEOUT", "UNSUCCESSFUL_HTTP_RESPONSE_CODE",
     * "INVALID_RESPONSE", "OTHER".
     *
     * @see https://developer.apple.com/documentation/appstoreserverapi/sendattemptresult
     */
    public readonly ?string $firstSendAttemptResult;

    /**
     * All delivery attempts for this test notification, in chronological order.
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

        $raw     = is_array($data['sendAttempts'] ?? null) ? $data['sendAttempts'] : [];
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
     * Returns true if the first delivery attempt was successful.
     */
    public function wasDelivered(): bool
    {
        return $this->firstSendAttemptResult === 'SUCCESS';
    }
}
