<?php

namespace ReceiptValidator\Amazon;

use DateTimeImmutable;
use ReceiptValidator\AbstractTransaction;

/**
 * Encapsulates a single transaction from an Amazon receipt.
 *
 * This immutable data object provides structured access to the properties of a
 * single purchase, parsed from the Amazon RVS response.
 */
final class Transaction extends AbstractTransaction
{
    /**
     * The date the purchase was initiated.
     */
    public readonly ?DateTimeImmutable $purchaseDate;

    /**
     * The date the subscription or entitlement was cancelled.
     */
    public readonly ?DateTimeImmutable $cancellationDate;

    /**
     * The date a subscription is scheduled to renew.
     */
    public readonly ?DateTimeImmutable $renewalDate;

    /**
     * The end date of a grace period for a subscription.
     */
    public readonly ?DateTimeImmutable $gracePeriodEndDate;

    /**
     * The end date of a free trial period.
     */
    public readonly ?DateTimeImmutable $freeTrialEndDate;

    /**
     * The auto-renewal status of a subscription.
     */
    public readonly ?bool $autoRenewing;

    /**
     * The duration of the subscription term.
     */
    public readonly ?string $term;

    /**
     * The SKU for the subscription term.
     */
    public readonly ?string $termSku;

    /**
     * Constructs the Transaction object and initializes its state.
     *
     * @param array<string, mixed> $data The raw data for a single transaction.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        // Initialize parent's readonly properties by mapping Amazon-specific fields.
        $this->quantity = (int) ($data['quantity'] ?? 1);
        $this->productId = $data['productId'] ?? null;
        $this->transactionId = $data['receiptId'] ?? null;

        // Initialize properties specific to this transaction type.
        $this->autoRenewing = isset($data['AutoRenewing']) ? (bool) $data['AutoRenewing'] : null;
        $this->term = $data['term'] ?? null;
        $this->termSku = $data['termSku'] ?? null;

        // Parse millisecond timestamps into immutable DateTime objects.
        $this->purchaseDate = isset($data['purchaseDate']) ? (new DateTimeImmutable())->setTimestamp((int) ($data['purchaseDate'] / 1000)) : null;
        $this->cancellationDate = isset($data['cancelDate']) ? (new DateTimeImmutable())->setTimestamp((int) ($data['cancelDate'] / 1000)) : null;
        $this->renewalDate = isset($data['renewalDate']) ? (new DateTimeImmutable())->setTimestamp((int) ($data['renewalDate'] / 1000)) : null;
        $this->gracePeriodEndDate = isset($data['GracePeriodEndDate']) ? (new DateTimeImmutable())->setTimestamp((int) ($data['GracePeriodEndDate'] / 1000)) : null;
        $this->freeTrialEndDate = isset($data['freeTrialEndDate']) ? (new DateTimeImmutable())->setTimestamp((int) ($data['freeTrialEndDate'] / 1000)) : null;
    }

    public function getPurchaseDate(): ?DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function getCancellationDate(): ?DateTimeImmutable
    {
        return $this->cancellationDate;
    }

    public function getRenewalDate(): ?DateTimeImmutable
    {
        return $this->renewalDate;
    }

    public function getGracePeriodEndDate(): ?DateTimeImmutable
    {
        return $this->gracePeriodEndDate;
    }

    public function getFreeTrialEndDate(): ?DateTimeImmutable
    {
        return $this->freeTrialEndDate;
    }

    public function isAutoRenewing(): ?bool
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
