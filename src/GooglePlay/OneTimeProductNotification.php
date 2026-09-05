<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * The `oneTimeProductNotification` payload of a Real-time Developer Notification.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#one-time
 */
final readonly class OneTimeProductNotification
{
    use ValueCasting;

    /** Notification schema version. */
    public ?string $version;

    /** The event type. UNKNOWN if Google added a type this library does not recognise. */
    public OneTimeProductNotificationType $notificationType;

    /** The raw integer type, useful for logging unknown values. */
    public int $rawNotificationType;

    /** The purchase token of the affected purchase. */
    public string $purchaseToken;

    /** The one-time product ID. */
    public ?string $sku;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->version             = $this->toString($data, 'version');
        $this->rawNotificationType = $this->toInt($data, 'notificationType') ?? 0;
        $this->notificationType    = OneTimeProductNotificationType::fromInt($this->rawNotificationType);
        $this->purchaseToken       = $this->toString($data, 'purchaseToken') ?? '';
        $this->sku                 = $this->toString($data, 'sku');
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getNotificationType(): OneTimeProductNotificationType
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

    public function getSku(): ?string
    {
        return $this->sku;
    }
}
