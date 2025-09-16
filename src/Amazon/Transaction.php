<?php

namespace ReceiptValidator\Amazon;

use Carbon\Carbon;
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
    public readonly ?Carbon $purchaseDate;

    /**
     * The date the subscription or entitlement was cancelled.
     */
    public readonly ?Carbon $cancellationDate;

    /**
     * The date a subscription is scheduled to renew.
     */
    public readonly ?Carbon $renewalDate;

    /**
     * The end date of a grace period for a subscription.
     */
    public readonly ?Carbon $gracePeriodEndDate;

    /**
     * The end date of a free trial period.
     */
    public readonly ?Carbon $freeTrialEndDate;

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

        if (!empty($data['purchaseDate'])) {
            $this->purchaseDate = Carbon::createFromTimestampMs($data['purchaseDate']);
        } else {
            $this->purchaseDate = null;
        }

        if (!empty($data['cancelDate'])) {
            $this->cancellationDate = Carbon::createFromTimestampMs($data['cancelDate']);
        } else {
            $this->cancellationDate = null;
        }

        if (!empty($data['renewalDate'])) {
            $this->renewalDate = Carbon::createFromTimestampMs($data['renewalDate']);
        } else {
            $this->renewalDate = null;
        }

        if (!empty($data['GracePeriodEndDate'])) {
            $this->gracePeriodEndDate = Carbon::createFromTimestampMs($data['GracePeriodEndDate']);
        } else {
            $this->gracePeriodEndDate = null;
        }

        if (!empty($data['freeTrialEndDate'])) {
            $this->freeTrialEndDate = Carbon::createFromTimestampMs($data['freeTrialEndDate']);
        } else {
            $this->freeTrialEndDate = null;
        }
    }

    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchaseDate;
    }

    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellationDate;
    }

    public function getRenewalDate(): ?Carbon
    {
        return $this->renewalDate;
    }

    public function getGracePeriodEndDate(): ?Carbon
    {
        return $this->gracePeriodEndDate;
    }

    public function getFreeTrialEndDate(): ?Carbon
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
