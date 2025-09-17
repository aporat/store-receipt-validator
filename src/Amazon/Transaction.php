<?php

declare(strict_types=1);

namespace ReceiptValidator\Amazon;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * Encapsulates a single transaction from an Amazon receipt.
 *
 * This immutable data object provides structured access to the properties of a
 * single purchase, parsed from the Amazon RVS response.
 */
final readonly class Transaction extends AbstractTransaction
{
    /** The date the purchase was initiated. */
    public ?CarbonImmutable $purchaseDate;

    /** The date the subscription or entitlement was cancelled. */
    public ?CarbonImmutable $cancellationDate;

    /** The date a subscription is scheduled to renew. */
    public ?CarbonImmutable $renewalDate;

    /** The end date of a grace period for a subscription. */
    public ?CarbonImmutable $gracePeriodEndDate;

    /** The end date of a free trial period. */
    public ?CarbonImmutable $freeTrialEndDate;

    /** The auto-renewal status of a subscription. */
    public bool $autoRenewing;

    /** The duration of the subscription term. */
    public ?string $term;

    /** The SKU for the subscription term. */
    public ?string $termSku;

    /**
     * @param array<string, mixed> $data The raw data for a single transaction.
     */
    public function __construct(array $data = [])
    {
        parent::__construct(
            rawData: $data,
            quantity: $this->toInt($data, 'quantity') ?? 1,
            productId: $this->toString($data, 'productId'),
            transactionId: $this->toString($data, 'receiptId'),
        );

        $this->autoRenewing = $this->toBool($data, 'AutoRenewing');
        $this->term         = $this->toString($data, 'term');
        $this->termSku      = $this->toString($data, 'termSku');

        $this->purchaseDate       = $this->toDateFromMs($data, 'purchaseDate');
        $this->cancellationDate   = $this->toDateFromMs($data, 'cancelDate');
        $this->renewalDate        = $this->toDateFromMs($data, 'renewalDate');
        $this->gracePeriodEndDate = $this->toDateFromMs($data, 'GracePeriodEndDate');
        $this->freeTrialEndDate   = $this->toDateFromMs($data, 'freeTrialEndDate');
    }

    public function getPurchaseDate(): ?CarbonInterface
    {
        return $this->purchaseDate;
    }
    public function getCancellationDate(): ?CarbonInterface
    {
        return $this->cancellationDate;
    }
    public function getRenewalDate(): ?CarbonInterface
    {
        return $this->renewalDate;
    }
    public function getGracePeriodEndDate(): ?CarbonInterface
    {
        return $this->gracePeriodEndDate;
    }
    public function getFreeTrialEndDate(): ?CarbonInterface
    {
        return $this->freeTrialEndDate;
    }

    public function isAutoRenewing(): bool
    {
        return $this->autoRenewing;
    }
    public function getTerm(): ?string
    {
        return $this->term;
    }
    public function getTermSku(): ?string
    {
        return $this->termSku;
    }
}
