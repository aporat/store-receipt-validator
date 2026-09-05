<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use ReceiptValidator\Support\ValueCasting;

/**
 * The `voidedPurchaseNotification` payload of a Real-time Developer Notification,
 * sent when a purchase is refunded or charged back.
 *
 * @see https://developer.android.com/google/play/billing/rtdn-reference#voided-purchase
 */
final readonly class VoidedPurchaseNotification
{
    use ValueCasting;

    /** The purchase token of the voided purchase. */
    public string $purchaseToken;

    /** The order ID of the voided purchase. */
    public ?string $orderId;

    /** Whether a subscription or a one-time product was voided. */
    public VoidedProductType $productType;

    /** Whether the refund was full or a quantity-based partial refund. */
    public RefundType $refundType;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->purchaseToken = $this->toString($data, 'purchaseToken') ?? '';
        $this->orderId       = $this->toString($data, 'orderId');
        $this->productType   = VoidedProductType::fromInt($this->toInt($data, 'productType') ?? 0);
        $this->refundType    = RefundType::fromInt($this->toInt($data, 'refundType') ?? 0);
    }

    public function getPurchaseToken(): string
    {
        return $this->purchaseToken;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getProductType(): VoidedProductType
    {
        return $this->productType;
    }

    public function getRefundType(): RefundType
    {
        return $this->refundType;
    }
}
