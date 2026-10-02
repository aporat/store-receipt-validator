<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * The `pendingRefundReviewNotification` payload of a Real-time Developer Notification,
 * sent when a chargeback is awaiting the developer's review.
 *
 * Respond through the `orders.reviewrefund` endpoint with the pending refund token,
 * which identifies the request rather than the purchase.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#pending-refund-review
 */
final readonly class PendingRefundReviewNotification
{
    use ValueCasting;

    /** Notification schema version. */
    public ?string $version;

    /** Token identifying the pending refund request; pass it to `orders.reviewrefund`. */
    public string $pendingRefundToken;

    /** The order ID of the purchase under review. */
    public ?string $orderId;

    /** Why the refund was requested, or null for a value this library does not know. Only CHARGEBACK is sent today. */
    public ?VoidedReason $refundReason;

    /** The raw integer reason, useful for logging unknown values. */
    public int $rawRefundReason;

    /** The obfuscated account ID set via `setObfuscatedAccountId()` in the billing client. */
    public ?string $obfuscatedAccountId;

    /** The obfuscated profile ID set via `setObfuscatedProfileId()` in the billing client. */
    public ?string $obfuscatedProfileId;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->version             = $this->toString($data, 'version');
        $this->pendingRefundToken  = $this->toString($data, 'pendingRefundToken') ?? '';
        $this->orderId             = $this->toString($data, 'orderId');
        $this->rawRefundReason     = $this->toInt($data, 'refundReason') ?? 0;
        $this->refundReason        = VoidedReason::tryFrom($this->rawRefundReason);
        $this->obfuscatedAccountId = $this->toString($data, 'obfuscatedAccountId');
        $this->obfuscatedProfileId = $this->toString($data, 'obfuscatedProfileId');
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getPendingRefundToken(): string
    {
        return $this->pendingRefundToken;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getRefundReason(): ?VoidedReason
    {
        return $this->refundReason;
    }

    public function getRawRefundReason(): int
    {
        return $this->rawRefundReason;
    }

    public function getObfuscatedAccountId(): ?string
    {
        return $this->obfuscatedAccountId;
    }

    public function getObfuscatedProfileId(): ?string
    {
        return $this->obfuscatedProfileId;
    }
}
