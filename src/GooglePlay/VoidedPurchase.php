<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * A purchase that was refunded, charged back or otherwise voided.
 *
 * The transaction ID exposed via the base class is the order ID. Google does not
 * include the product ID in voided purchase records.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.voidedpurchases#VoidedPurchase
 */
final readonly class VoidedPurchase extends AbstractTransaction
{
    /** Resource kind, always "androidpublisher#voidedPurchase". */
    public ?string $kind;

    /** The purchase token of the voided purchase. */
    public ?string $purchaseToken;

    /** The order ID of the voided purchase. */
    public ?string $orderId;

    /** When the purchase was originally made. */
    public ?CarbonImmutable $purchaseTime;

    /** When the purchase was voided. */
    public ?CarbonImmutable $voidedTime;

    /** Who voided the purchase, or null for an unknown value. */
    public ?VoidedSource $voidedSource;

    /** Why the purchase was voided, or null for an unknown value. */
    public ?VoidedReason $voidedReason;

    /** For quantity-based partial refunds, the number of units voided. */
    public ?int $voidedQuantity;

    /**
     * @param array<string, mixed> $data A single entry from `voidedPurchases`.
     */
    public function __construct(array $data = [])
    {
        $voidedQuantity = $this->toInt($data, 'voidedQuantity');

        parent::__construct(
            rawData: $data,
            quantity: $voidedQuantity ?? 1,
            productId: null,
            transactionId: $this->toString($data, 'orderId'),
        );

        $this->kind           = $this->toString($data, 'kind');
        $this->purchaseToken  = $this->toString($data, 'purchaseToken');
        $this->orderId        = $this->toString($data, 'orderId');
        $this->purchaseTime   = $this->toDateFromMs($data, 'purchaseTimeMillis');
        $this->voidedTime     = $this->toDateFromMs($data, 'voidedTimeMillis');
        $this->voidedQuantity = $voidedQuantity;

        $source = $this->toInt($data, 'voidedSource');
        $reason = $this->toInt($data, 'voidedReason');

        $this->voidedSource = $source !== null ? VoidedSource::tryFrom($source) : null;
        $this->voidedReason = $reason !== null ? VoidedReason::tryFrom($reason) : null;
    }

    public function getKind(): ?string
    {
        return $this->kind;
    }

    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getPurchaseTime(): ?CarbonInterface
    {
        return $this->purchaseTime;
    }

    public function getVoidedTime(): ?CarbonInterface
    {
        return $this->voidedTime;
    }

    public function getVoidedSource(): ?VoidedSource
    {
        return $this->voidedSource;
    }

    public function getVoidedReason(): ?VoidedReason
    {
        return $this->voidedReason;
    }

    public function getVoidedQuantity(): ?int
    {
        return $this->voidedQuantity;
    }
}
