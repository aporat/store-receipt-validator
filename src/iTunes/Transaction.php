<?php

namespace ReceiptValidator\iTunes;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\AbstractTransaction;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

/**
 * @implements ArrayAccess<string, mixed>
 */
class Transaction extends AbstractTransaction implements ArrayAccess
{
    /**
     * Web order line item ID.
     *
     * @var string|null
     */
    protected ?string $webOrderLineItemId = null;

    /**
     * Original transaction ID.
     *
     * @var string
     */
    protected string $originalTransactionId;

    /**
     * Purchase date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $purchaseDate = null;

    /**
     * Original purchase date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $originalPurchaseDate = null;

    /**
     * Expires date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $expiresDate = null;

    /**
     * Cancellation date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $cancellationDate = null;

    /**
     * Whether it’s a trial period.
     *
     * @var bool|null
     */
    protected ?bool $isTrialPeriod = null;

    /**
     * Whether it's an introductory offer.
     *
     * @var bool|null
     */
    protected ?bool $isInIntroOfferPeriod = null;

    /**
     * Promotional offer ID.
     *
     * @var string|null
     */
    protected ?string $promotionalOfferId = null;

    public function getWebOrderLineItemId(): ?string
    {
        return $this->webOrderLineItemId;
    }

    public function getOriginalTransactionId(): string
    {
        return $this->originalTransactionId;
    }

    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchaseDate;
    }

    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->originalPurchaseDate;
    }

    public function getExpiresDate(): ?Carbon
    {
        return $this->expiresDate;
    }

    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellationDate;
    }

    public function isTrialPeriod(): ?bool
    {
        return $this->isTrialPeriod;
    }

    public function isInIntroOfferPeriod(): ?bool
    {
        return $this->isInIntroOfferPeriod;
    }

    public function getPromotionalOfferId(): ?string
    {
        return $this->promotionalOfferId;
    }

    public function hasExpired(): bool
    {
        return $this->expiresDate !== null && $this->expiresDate->isPast();
    }

    public function wasCanceled(): bool
    {
        return $this->cancellationDate !== null;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->rawData[$offset] = $value;
        $this->parse();
    }

    /**
     * Parse Data from JSON Response.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parse(): self
    {
        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be an array');
        }

        $this->setQuantity((int)($this->rawData['quantity'] ?? 0));
        $this->setTransactionId($this->rawData['transaction_id'] ?? '');
        $this->setProductId($this->rawData['product_id'] ?? '');

        $this->originalTransactionId = $this->rawData['original_transaction_id'] ?? '';
        $this->webOrderLineItemId = $this->rawData['web_order_line_item_id'] ?? null;
        $this->promotionalOfferId = $this->rawData['promotional_offer_id'] ?? null;

        $this->isTrialPeriod = isset($this->rawData['is_trial_period'])
            ? filter_var($this->rawData['is_trial_period'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $this->isInIntroOfferPeriod = isset($this->rawData['is_in_intro_offer_period'])
            ? filter_var($this->rawData['is_in_intro_offer_period'], FILTER_VALIDATE_BOOLEAN)
            : null;

        if (!empty($this->rawData['purchase_date_ms'])) {
            $this->purchaseDate = Carbon::createFromTimestampUTC((int)($this->rawData['purchase_date_ms'] / 1000));
        }

        if (!empty($this->rawData['original_purchase_date_ms'])) {
            $this->originalPurchaseDate = Carbon::createFromTimestampUTC((int)($this->rawData['original_purchase_date_ms'] / 1000));
        }

        if (!empty($this->rawData['expires_date_ms'])) {
            $this->expiresDate = Carbon::createFromTimestampUTC((int)($this->rawData['expires_date_ms'] / 1000));
        } elseif (!empty($this->rawData['expires_date']) && is_numeric($this->rawData['expires_date'])) {
            $this->expiresDate = Carbon::createFromTimestampUTC((int)($this->rawData['expires_date'] / 1000));
        }

        if (!empty($this->rawData['cancellation_date_ms'])) {
            $this->cancellationDate = Carbon::createFromTimestampUTC((int)($this->rawData['cancellation_date_ms'] / 1000));
        }

        return $this;
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
