<?php

namespace ReceiptValidator\Amazon;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\AbstractTransaction;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

/**
 * Represents a transaction in Amazon receipt validation.
 * @implements ArrayAccess<string, mixed>
 */
class Transaction extends AbstractTransaction implements ArrayAccess
{
    /** @var Carbon Purchase date. */
    protected Carbon $purchaseDate;

    /** @var Carbon|null Cancellation date. */
    protected ?Carbon $cancellationDate = null;

    /** @var Carbon|null Renewal date. */
    protected ?Carbon $renewalDate = null;

    /** @var Carbon|null Grace period end date. */
    protected ?Carbon $gracePeriodEndDate = null;

    /** @var Carbon|null Free trial end date. */
    protected ?Carbon $freeTrialEndDate = null;

    /** @var bool|null Auto-renewing status. */
    protected ?bool $autoRenewing = null;

    /** @var string|null Subscription term duration. */
    protected ?string $term = null;

    /** @var string|null Subscription term SKU. */
    protected ?string $termSku = null;

    /**
     * Parses raw transaction data.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parse(): self
    {
        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be an array');
        }

        $data = $this->rawData;

        $this->setQuantity((int)($data['quantity'] ?? 0));
        $this->setTransactionId($data['receiptId'] ?? '');
        $this->setProductId($data['productId'] ?? '');

        if (!empty($data['purchaseDate'])) {
            $this->purchaseDate = Carbon::createFromTimestampUTC((int)($data['purchaseDate'] / 1000));
        }

        if (!empty($data['cancelDate'])) {
            $this->cancellationDate = Carbon::createFromTimestampUTC((int)($data['cancelDate'] / 1000));
        }

        if (!empty($data['renewalDate'])) {
            $this->renewalDate = Carbon::createFromTimestampUTC((int)($data['renewalDate'] / 1000));
        }

        if (!empty($data['GracePeriodEndDate'])) {
            $this->gracePeriodEndDate = Carbon::createFromTimestampUTC((int)($data['GracePeriodEndDate'] / 1000));
        }

        if (!empty($data['freeTrialEndDate'])) {
            $this->freeTrialEndDate = Carbon::createFromTimestampUTC((int)($data['freeTrialEndDate'] / 1000));
        }

        $this->autoRenewing = isset($data['AutoRenewing']) ? (bool)$data['AutoRenewing'] : null;
        $this->term = $data['term'] ?? null;
        $this->termSku = $data['termSku'] ?? null;

        return $this;
    }

    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    public function getPurchaseDate(): Carbon
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

    /**
     * @throws ValidationException
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->rawData[$offset] = $value;
        $this->parse();
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        return $this->rawData[$offset] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->rawData[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->rawData[$offset]);
    }
}
