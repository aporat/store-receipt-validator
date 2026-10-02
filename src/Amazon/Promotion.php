<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use ReceiptValidator\Support\ValueCasting;

/**
 * One entry of the RVS `promotions` list: a promotional price or retention offer
 * applied to a subscription purchase.
 *
 * Unknown type or status strings are kept in the raw data and exposed as null.
 *
 * @see https://developer.amazon.com/docs/in-app-purchasing/iap-rvs-for-android-apps.html#promotions
 */
final readonly class Promotion
{
    use ValueCasting;

    public ?PromotionType $type;

    public ?PromotionStatus $status;

    /**
     * @param array<string, mixed> $rawData The decoded promotion object.
     */
    public function __construct(private array $rawData = [])
    {
        $this->type   = PromotionType::tryFrom($this->toString($rawData, 'promotionType') ?? '');
        $this->status = PromotionStatus::tryFrom($this->toString($rawData, 'promotionStatus') ?? '');
    }

    /** @return array<string, mixed> */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getType(): ?PromotionType
    {
        return $this->type;
    }

    public function getStatus(): ?PromotionStatus
    {
        return $this->status;
    }

    /** Whether the customer is currently benefiting from this promotion. */
    public function isActive(): bool
    {
        return $this->status?->isActive() ?? false;
    }
}
