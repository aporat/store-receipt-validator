<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * The `subscriptionNotification` payload of a Real-time Developer Notification.
 *
 * This is a pointer, not a receipt: it identifies the purchase token, and the
 * authoritative state must be re-read via {@see Validator::getSubscriptionPurchaseV2()}.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#sub
 */
final readonly class SubscriptionNotification
{
    use ValueCasting;

    /** Notification schema version. */
    public ?string $version;

    /** The event type. UNKNOWN if Google added a type this library does not recognise. */
    public SubscriptionNotificationType $notificationType;

    /** The raw integer type, useful for logging unknown values. */
    public int $rawNotificationType;

    /** The purchase token of the affected subscription. */
    public string $purchaseToken;

    /** The subscription product ID (Google still calls this `subscriptionId`). */
    public ?string $subscriptionId;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->version             = $this->toString($data, 'version');
        $this->rawNotificationType = $this->toInt($data, 'notificationType') ?? 0;
        $this->notificationType    = SubscriptionNotificationType::fromInt($this->rawNotificationType);
        $this->purchaseToken       = $this->toString($data, 'purchaseToken') ?? '';
        $this->subscriptionId      = $this->toString($data, 'subscriptionId');
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getNotificationType(): SubscriptionNotificationType
    {
        return $this->notificationType;
    }

    public function getRawNotificationType(): int
    {
        return $this->rawNotificationType;
    }

    public function getPurchaseToken(): string
    {
        return $this->purchaseToken;
    }

    public function getSubscriptionId(): ?string
    {
        return $this->subscriptionId;
    }
}
