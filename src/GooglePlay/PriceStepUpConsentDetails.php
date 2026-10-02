<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\Support\ValueCasting;

/**
 * A price step-up awaiting or holding the user's consent (`priceStepUpConsentDetails`).
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#PriceStepUpConsentDetails
 */
final readonly class PriceStepUpConsentDetails
{
    use ValueCasting;

    public ConsentState $state;

    /** The deadline for the user to consent before the subscription is affected. */
    public ?CarbonImmutable $consentDeadlineTime;

    /** The stepped-up price. */
    public ?Money $newPrice;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->state               = ConsentState::fromString($this->toString($data, 'state'));
        $this->consentDeadlineTime = $this->toDateFromRfc3339($data, 'consentDeadlineTime');
        $this->newPrice            = Money::fromArray($data['newPrice'] ?? null);
    }

    public function getState(): ConsentState
    {
        return $this->state;
    }

    public function getConsentDeadlineTime(): ?CarbonInterface
    {
        return $this->consentDeadlineTime;
    }

    public function getNewPrice(): ?Money
    {
        return $this->newPrice;
    }

    public function isPending(): bool
    {
        return $this->state === ConsentState::PENDING;
    }
}
