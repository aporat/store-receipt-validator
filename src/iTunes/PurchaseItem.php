<?php

namespace ReceiptValidator\iTunes;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\RunTimeException;

class PurchaseItem implements ArrayAccess
{
    /**
     * quantity.
     *
     * @var int
     */
    protected int $quantity = 0;

    /**
     * product_id.
     *
     * @var string
     */
    protected string $product_id;

    /**
     * web_order_line_item_id.
     *
     * @var string|null
     */
    protected ?string $web_order_line_item_id;

    /**
     * transaction_id.
     *
     * @var string
     */
    protected string $transaction_id;

    /**
     * original_transaction_id.
     *
     * @var string
     */
    protected string $original_transaction_id;

    /**
     * purchase_date.
     *
     * @var Carbon
     */
    protected Carbon $purchase_date;

    /**
     * original_purchase_date.
     *
     * @var Carbon
     */
    protected Carbon $original_purchase_date;

    /**
     * expires_date.
     *
     * @var Carbon
     */
    protected Carbon $expires_date;

    /**
     * cancellation_date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $cancellation_date;

    /**
     * For a subscription, whether it is in the free trial period.
     *
     * @var bool|null
     */
    protected ?bool $is_trial_period;

    /**
     * For an auto-renewable subscription, whether it is in the introductory price period.
     *
     * @var bool|null
     */
    protected ?bool $is_in_intro_offer_period;

    /**
     * When a subscriber redeems an offer, there is a promotional offer ID.
     *
     * @var string|null
     */
    protected ?string $promotional_offer_id;

    /**
     * purchase item info.
     *
     * @var array|null
     */
    protected ?array $raw_data;

    /**
     * PurchaseItem constructor.
     *
     * @param array|null $data
     *
     * @throws RunTimeException
     */
    public function __construct(?array $data)
    {
        $this->raw_data = $data;
        $this->parseData();
    }

    /**
     * Parse Data from JSON Response.
     *
     * @throws RunTimeException
     *
     * @return $this
     */
    public function parseData(): self
    {
        if (!is_array($this->raw_data)) {
            throw new RuntimeException('Response must be an array');
        }

        if (array_key_exists('quantity', $this->raw_data)) {
            $this->quantity = (int) $this->raw_data['quantity'];
        }

        if (array_key_exists('transaction_id', $this->raw_data)) {
            $this->transaction_id = $this->raw_data['transaction_id'];
        }

        if (array_key_exists('original_transaction_id', $this->raw_data)) {
            $this->original_transaction_id = $this->raw_data['original_transaction_id'];
        }

        if (array_key_exists('product_id', $this->raw_data)) {
            $this->product_id = $this->raw_data['product_id'];
        }

        if (array_key_exists('web_order_line_item_id', $this->raw_data)) {
            $this->web_order_line_item_id = $this->raw_data['web_order_line_item_id'];
        }

        if (array_key_exists('promotional_offer_id', $this->raw_data)) {
            $this->promotional_offer_id = $this->raw_data['promotional_offer_id'];
        }

        if (array_key_exists('is_trial_period', $this->raw_data)) {
            $this->is_trial_period = filter_var($this->raw_data['is_trial_period'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('is_in_intro_offer_period', $this->raw_data)) {
            $this->is_in_intro_offer_period = filter_var(
                $this->raw_data['is_in_intro_offer_period'],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        if (array_key_exists('purchase_date_ms', $this->raw_data)) {
            $this->purchase_date = Carbon::createFromTimestampUTC(
                (int) round($this->raw_data['purchase_date_ms'] / 1000)
            );
        }

        if (array_key_exists('original_purchase_date_ms', $this->raw_data)) {
            $this->original_purchase_date = Carbon::createFromTimestampUTC(
                (int) round($this->raw_data['original_purchase_date_ms'] / 1000)
            );
        }

        if (array_key_exists('expires_date_ms', $this->raw_data)) {
            $this->expires_date = Carbon::createFromTimestampUTC((int) round($this->raw_data['expires_date_ms'] / 1000));
        } elseif (array_key_exists('expires_date', $this->raw_data) && is_numeric($this->raw_data['expires_date'])) {
            $this->expires_date = Carbon::createFromTimestampUTC(
                (int) round((int) $this->raw_data['expires_date'] / 1000)
            );
        }

        if (array_key_exists('cancellation_date_ms', $this->raw_data)) {
            $this->cancellation_date = Carbon::createFromTimestampUTC(
                (int) round($this->raw_data['cancellation_date_ms'] / 1000)
            );
        }

        return $this;
    }

    /**
     * @return array|null
     */
    public function getRawResponse(): ?array
    {
        return $this->raw_data;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return bool|null
     */
    public function isTrialPeriod(): ?bool
    {
        return $this->is_trial_period;
    }

    /**
     * @return bool|null
     */
    public function isInIntroOfferPeriod(): ?bool
    {
        return $this->is_in_intro_offer_period;
    }

    /**
     * @return string|null
     */
    public function getPromotionalOfferId(): ?string
    {
        return $this->promotional_offer_id;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->product_id;
    }

    /**
     * @return null|string
     */
    public function getWebOrderLineItemId(): ?string
    {
        return $this->web_order_line_item_id;
    }

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transaction_id;
    }

    /**
     * @return string
     */
    public function getOriginalTransactionId(): string
    {
        return $this->original_transaction_id;
    }

    /**
     * @return Carbon|null
     */
    public function getPurchaseDate(): ?Carbon
    {
        return $this->purchase_date;
    }

    /**
     * @return Carbon|null
     */
    public function getOriginalPurchaseDate(): ?Carbon
    {
        return $this->original_purchase_date;
    }

    /**
     * @return Carbon|null
     */
    public function getExpiresDate(): ?Carbon
    {
        return $this->expires_date;
    }

    /**
     * @return Carbon|null
     */
    public function getCancellationDate(): ?Carbon
    {
        return $this->cancellation_date;
    }

    /**
     * Update a response key and reprocess object properties.
     *
     * @param $key
     * @param $value
     *
     * @throws RunTimeException
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($key, $value): void
    {
        $this->raw_data[$key] = $value;
        $this->parseData();
    }

    /**
     * Get a response key.
     *
     * @param $key
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key): mixed
    {
        return $this->raw_data[$key];
    }

    /**
     * Unset a response key.
     *
     * @param $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key): void
    {
        unset($this->raw_data[$key]);
    }

    /**
     * Check if response key exists.
     *
     * @param $key
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($key): bool
    {
        return isset($this->raw_data[$key]);
    }
}
