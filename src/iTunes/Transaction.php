<?php

namespace ReceiptValidator\iTunes;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\AbstractTransaction;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

class Transaction extends AbstractTransaction implements ArrayAccess
{
    /**
     * Web order line item ID.
     *
     * @var string|null
     */
    protected ?string $web_order_line_item_id = null;

    /**
     * Original transaction ID.
     *
     * @var string
     */
    protected string $original_transaction_id;

    /**
     * Purchase date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $purchase_date = null;

    /**
     * Original purchase date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $original_purchase_date = null;

    /**
     * Expires date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $expires_date = null;

    /**
     * Cancellation date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $cancellation_date = null;

    /**
     * Whether it’s a trial period.
     *
     * @var bool|null
     */
    protected ?bool $is_trial_period = null;

    /**
     * Whether it's an introductory offer.
     *
     * @var bool|null
     */
    protected ?bool $is_in_intro_offer_period = null;

    /**
     * Promotional offer ID.
     *
     * @var string|null
     */
    protected ?string $promotional_offer_id = null;

    /**
     * Raw transaction data.
     *
     * @var array|null
     */
    protected ?array $raw_data;

    /**
     * Constructor.
     *
     * @param array|null $data
     * @throws ValidationException
     */
    public function __construct(?array $data)
    {
        $this->raw_data = $data;
        $this->parseData();
    }

    /**
     * Parse Data from JSON Response.
     *
     * @return $this
     * @throws ValidationException
     */
    public function parseData(): self
    {
        if (!is_array($this->raw_data)) {
            throw new ValidationException('Response must be an array');
        }

        $this->setQuantity((int)($this->raw_data['quantity'] ?? 0));
        $this->setTransactionId($this->raw_data['transaction_id'] ?? '');
        $this->setProductId($this->raw_data['product_id'] ?? '');

        $this->original_transaction_id = $this->raw_data['original_transaction_id'] ?? '';
        $this->web_order_line_item_id = $this->raw_data['web_order_line_item_id'] ?? null;
        $this->promotional_offer_id = $this->raw_data['promotional_offer_id'] ?? null;

        $this->is_trial_period = isset($this->raw_data['is_trial_period'])
            ? filter_var($this->raw_data['is_trial_period'], FILTER_VALIDATE_BOOLEAN)
            : null;

        $this->is_in_intro_offer_period = isset($this->raw_data['is_in_intro_offer_period'])
            ? filter_var($this->raw_data['is_in_intro_offer_period'], FILTER_VALIDATE_BOOLEAN)
            : null;

        if (!empty($this->raw_data['purchase_date_ms'])) {
            $this->purchase_date = Carbon::createFromTimestampUTC((int)($this->raw_data['purchase_date_ms'] / 1000));
        }

        if (!empty($this->raw_data['original_purchase_date_ms'])) {
            $this->original_purchase_date = Carbon::createFromTimestampUTC((int)($this->raw_data['original_purchase_date_ms'] / 1000));
        }

        if (!empty($this->raw_data['expires_date_ms'])) {
            $this->expires_date = Carbon::createFromTimestampUTC((int)($this->raw_data['expires_date_ms'] / 1000));
        } elseif (!empty($this->raw_data['expires_date']) && is_numeric($this->raw_data['expires_date'])) {
            $this->expires_date = Carbon::createFromTimestampUTC((int)($this->raw_data['expires_date'] / 1000));
        }

        if (!empty($this->raw_data['cancellation_date_ms'])) {
            $this->cancellation_date = Carbon::createFromTimestampUTC((int)($this->raw_data['cancellation_date_ms'] / 1000));
        }

        return $this;
    }

    public function getRawResponse(): ?array
    {
        return $this->raw_data;
    }

    public function getWebOrderLineItemId(): ?string
    {
        return $this->web_order_line_item_id;
    }

    public function getOriginalTransactionId(): string
    {
        return $this->original_transaction_id;
    }

    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchase_date;
    }

    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->original_purchase_date;
    }

    public function getExpiresDate(): ?Carbon
    {
        return $this->expires_date;
    }

    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellation_date;
    }

    public function isTrialPeriod(): ?bool
    {
        return $this->is_trial_period;
    }

    public function isInIntroOfferPeriod(): ?bool
    {
        return $this->is_in_intro_offer_period;
    }

    public function getPromotionalOfferId(): ?string
    {
        return $this->promotional_offer_id;
    }

    public function hasExpired(): bool
    {
        return $this->expires_date !== null && $this->expires_date->isPast();
    }

    public function wasCanceled(): bool
    {
        return $this->cancellation_date !== null;
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->raw_data[$offset] = $value;
        $this->parseData();
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset): mixed
    {
        return $this->raw_data[$offset] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->raw_data[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return isset($this->raw_data[$offset]);
    }
}
