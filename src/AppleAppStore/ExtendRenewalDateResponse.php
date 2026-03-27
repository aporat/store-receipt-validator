<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * Response from the Extend a Subscription Renewal Date endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/extend-a-subscription-renewal-date
 */
final readonly class ExtendRenewalDateResponse
{
    use ValueCasting;

    /** The original transaction identifier of the subscription. */
    public ?string $originalTransactionId;

    /** A unique identifier for a subscription-purchase event across devices. */
    public ?string $webOrderLineItemId;

    /** Whether the renewal date extension succeeded. */
    public bool $success;

    /** The new subscription expiration date after the extension. */
    public ?CarbonImmutable $effectiveDate;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->originalTransactionId = $this->toString($data, 'originalTransactionId');
        $this->webOrderLineItemId    = $this->toString($data, 'webOrderLineItemId');
        $this->success               = $this->toBool($data, 'success');
        $this->effectiveDate         = $this->toDateFromMs($data, 'effectiveDate');
    }

    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }

    public function getWebOrderLineItemId(): ?string
    {
        return $this->webOrderLineItemId;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getEffectiveDate(): ?CarbonInterface
    {
        return $this->effectiveDate;
    }
}
