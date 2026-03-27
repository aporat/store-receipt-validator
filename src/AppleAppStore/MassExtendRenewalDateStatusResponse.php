<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * Response from the Get Status of Subscription Renewal Date Extensions endpoint.
 *
 * @see https://developer.apple.com/documentation/appstoreserverapi/get-status-of-subscription-renewal-date-extensions
 */
final readonly class MassExtendRenewalDateStatusResponse
{
    use ValueCasting;

    /** The unique identifier of the mass extension request. */
    public ?string $requestIdentifier;

    /** Whether the App Store has finished processing the mass extension. */
    public bool $complete;

    /** The date the App Store completed processing the extension request. */
    public ?CarbonImmutable $completeDate;

    /** The count of subscriptions that successfully received the extension. */
    public ?int $succeededCount;

    /** The count of subscriptions that failed to receive the extension. */
    public ?int $failedCount;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->requestIdentifier = $this->toString($data, 'requestIdentifier');
        $this->complete          = $this->toBool($data, 'complete');
        $this->completeDate      = $this->toDateFromMs($data, 'completeDate');
        $this->succeededCount    = $this->toInt($data, 'succeededCount');
        $this->failedCount       = $this->toInt($data, 'failedCount');
    }

    public function getRequestIdentifier(): ?string
    {
        return $this->requestIdentifier;
    }

    public function isComplete(): bool
    {
        return $this->complete;
    }

    public function getCompleteDate(): ?CarbonInterface
    {
        return $this->completeDate;
    }

    public function getSucceededCount(): ?int
    {
        return $this->succeededCount;
    }

    public function getFailedCount(): ?int
    {
        return $this->failedCount;
    }
}
