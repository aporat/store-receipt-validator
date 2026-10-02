<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * The new expiry times returned by `purchases.subscriptionsv2.defer`, one per line item.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/defer#response-body
 */
final readonly class DeferSubscriptionResponse
{
    use ValueCasting;

    /**
     * New expiry time keyed by product ID.
     *
     * @var array<string, CarbonImmutable>
     */
    public array $itemExpiryTimes;

    /** @var array<string, mixed> */
    private array $rawData;

    /**
     * @param array<string, mixed> $data The decoded `DeferSubscriptionPurchaseResponse` JSON.
     */
    public function __construct(array $data = [])
    {
        $this->rawData = $data;

        $times = [];
        foreach (is_array($data['itemExpiryTimeDetails'] ?? null) ? $data['itemExpiryTimeDetails'] : [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $productId = $this->toString($item, 'productId');
            $expiry    = $this->toDateFromRfc3339($item, 'expiryTime');

            if ($productId !== null && $expiry !== null) {
                $times[$productId] = $expiry;
            }
        }

        $this->itemExpiryTimes = $times;
    }

    /**
     * New expiry time keyed by product ID.
     *
     * @return array<string, CarbonImmutable>
     */
    public function getItemExpiryTimes(): array
    {
        return $this->itemExpiryTimes;
    }

    /**
     * The new expiry time of one product, or of the furthest-expiring item when no
     * product ID is given.
     */
    public function getExpiryTime(?string $productId = null): ?CarbonInterface
    {
        if ($productId !== null) {
            return $this->itemExpiryTimes[$productId] ?? null;
        }

        $latest = null;
        foreach ($this->itemExpiryTimes as $expiry) {
            if ($latest === null || $expiry->greaterThan($latest)) {
                $latest = $expiry;
            }
        }

        return $latest;
    }

    /** @return array<string, mixed> */
    public function getRawData(): array
    {
        return $this->rawData;
    }
}
