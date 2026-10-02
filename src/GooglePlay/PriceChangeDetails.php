<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * A pending or applied price change on an auto-renewing plan (`priceChangeDetails`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#SubscriptionItemPriceChangeDetails
 */
final readonly class PriceChangeDetails
{
    use ValueCasting;

    /** The price the subscription will renew at. */
    public ?Money $newPrice;

    public PriceChangeMode $mode;

    public PriceChangeState $state;

    /** When the new price is expected to be charged first. */
    public ?CarbonImmutable $expectedNewPriceChargeTime;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->newPrice                   = Money::fromArray($data['newPrice'] ?? null);
        $this->mode                       = PriceChangeMode::fromString($this->toString($data, 'priceChangeMode'));
        $this->state                      = PriceChangeState::fromString($this->toString($data, 'priceChangeState'));
        $this->expectedNewPriceChargeTime = $this->toDateFromRfc3339($data, 'expectedNewPriceChargeTime');
    }

    public function getNewPrice(): ?Money
    {
        return $this->newPrice;
    }

    public function getMode(): PriceChangeMode
    {
        return $this->mode;
    }

    public function getState(): PriceChangeState
    {
        return $this->state;
    }

    public function getExpectedNewPriceChargeTime(): ?CarbonInterface
    {
        return $this->expectedNewPriceChargeTime;
    }

    /** True while the user still has to accept the change. */
    public function isOutstanding(): bool
    {
        return $this->state === PriceChangeState::OUTSTANDING;
    }
}
