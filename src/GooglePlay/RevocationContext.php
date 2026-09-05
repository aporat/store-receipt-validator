<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

/**
 * The refund to issue when revoking a subscription via `purchases.subscriptionsv2.revoke`.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/revoke#RevocationContext
 */
final readonly class RevocationContext
{
    private function __construct(
        /** @var array<string, mixed> */
        private array $context,
    ) {
    }

    /**
     * Refund the full amount of the current billing period.
     */
    public static function fullRefund(): self
    {
        return new self(['fullRefund' => new \stdClass()]);
    }

    /**
     * Refund the unused portion of the current billing period.
     */
    public static function proratedRefund(): self
    {
        return new self(['proratedRefund' => new \stdClass()]);
    }

    /**
     * Refund a single add-on item of a multi-item subscription.
     */
    public static function itemBasedRefund(string $productId): self
    {
        return new self(['itemBasedRefund' => ['productId' => $productId]]);
    }

    /**
     * @return array<string, mixed> The JSON request body.
     */
    public function toArray(): array
    {
        return ['revocationContext' => $this->context];
    }
}
