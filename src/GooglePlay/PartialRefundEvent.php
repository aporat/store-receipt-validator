<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * One partial refund on an order (`orderHistory.partialRefundEvents[]`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders#PartialRefundEvent
 */
final readonly class PartialRefundEvent
{
    use ValueCasting;

    /** When the partial refund was requested. */
    public ?CarbonImmutable $createTime;

    /** When the partial refund was processed. Absent while pending. */
    public ?CarbonImmutable $processTime;

    /** Raw state: PENDING or PROCESSED_SUCCESSFULLY. */
    public ?string $state;

    /** The amount refunded, including tax when the order is tax inclusive. */
    public ?Money $total;

    /** The tax refunded. */
    public ?Money $tax;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $details = is_array($data['refundDetails'] ?? null) ? $data['refundDetails'] : [];

        $this->createTime  = $this->toDateFromRfc3339($data, 'createTime');
        $this->processTime = $this->toDateFromRfc3339($data, 'processTime');
        $this->state       = $this->toString($data, 'state');
        $this->total       = Money::fromArray($details['total'] ?? null);
        $this->tax         = Money::fromArray($details['tax'] ?? null);
    }

    public function getCreateTime(): ?CarbonInterface
    {
        return $this->createTime;
    }

    public function getProcessTime(): ?CarbonInterface
    {
        return $this->processTime;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function isProcessed(): bool
    {
        return $this->state === 'PROCESSED_SUCCESSFULLY';
    }

    public function getTotal(): ?Money
    {
        return $this->total;
    }

    public function getTax(): ?Money
    {
        return $this->tax;
    }
}
