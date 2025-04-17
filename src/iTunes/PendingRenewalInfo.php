<?php

namespace ReceiptValidator\iTunes;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\RunTimeException;

class PendingRenewalInfo implements ArrayAccess
{
    /*!
     * Developer friendly field codes
     * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
     */

    // Expiration Intent Codes //
    /* @var int Customer Cancelled */
    public const int EXPIRATION_INTENT_CANCELLED = 1;

    /* @var int Billing Error */
    public const int EXPIRATION_INTENT_BILLING_ERROR = 2;

    /* @var int Recent price increase was declined */
    public const int EXPIRATION_INTENT_INCREASE_DECLINED = 3;

    /* @var int Product unavailable at time of renewal */
    public const int EXPIRATION_INTENT_PRODUCT_UNAVAILABLE = 4;

    /* @var int Unknown */
    public const int EXPIRATION_INTENT_UNKNOWN = 5;

    // Retry flag codes //
    /* @var int Still attempting renewal */
    public const int RETRY_PERIOD_ACTIVE = 1;

    /* @var int Stopped attempting renewal */
    public const int RETRY_PERIOD_INACTIVE = 0;

    // Auto-renew status codes //
    /* @var int Subscription will renew */
    public const int AUTO_RENEW_ACTIVE = 1;

    /* @var int Customer has turned off renewal */
    public const int AUTO_RENEW_INACTIVE = 0;

    /**
     * Computed status code.
     *
     * @var string
     */
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_EXPIRED = 'expired';

    /**
     * Product ID.
     *
     * @var string
     */
    protected string $product_id = '';

    /**
     * Auto Renew Product ID.
     *
     * @var string
     */
    protected string $auto_renew_product_id = '';

    /**
     * Original Transaction ID.
     *
     * @var string
     */
    protected string $original_transaction_id = '';

    /**
     * The current renewal status for the auto-renewable subscription.
     *
     * true - Subscription will renew at the end of the current subscription period.
     * false - Customer has turned off automatic renewal for their subscription
     *
     * @var bool
     */
    protected bool $auto_renew_status = false;

    /**
     * Expiration Intent Code.
     *
     * @var int|null
     */
    protected ?int $expiration_intent = null;

    /**
     * he time at which the grace period for subscription renewals expires.
     *
     * @var Carbon|null
     */
    protected ?Carbon $grace_period_expires_date = null;

    /**
     * Is In Billing Retry Period Code.
     *
     * @var int|null
     */
    protected ?int $is_in_billing_retry_period = null;

    /**
     * Pending renewal info.
     *
     * @var array|null
     */
    protected ?array $raw_data = null;

    /**
     * Response constructor.
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
     * @return $this
     * @throws RunTimeException
     *
     */
    public function parseData(): self
    {
        if (!is_array($this->raw_data)) {
            throw new RunTimeException('Response must be a scalar value');
        }

        $this->product_id = $this->raw_data['product_id'];
        $this->original_transaction_id = $this->raw_data['original_transaction_id'];
        $this->auto_renew_product_id = $this->raw_data['auto_renew_product_id'];
        $this->auto_renew_status = (bool)$this->raw_data['auto_renew_status'];

        if (array_key_exists('expiration_intent', $this->raw_data)) {
            $this->expiration_intent = (int)$this->raw_data['expiration_intent'];
        }

        if (array_key_exists('grace_period_expires_date_ms', $this->raw_data)) {
            $this->grace_period_expires_date = Carbon::createFromTimestampUTC(
                (int)round($this->raw_data['grace_period_expires_date_ms'] / 1000)
            );
        }

        if (array_key_exists('is_in_billing_retry_period', $this->raw_data)) {
            $this->is_in_billing_retry_period = (int)$this->raw_data['is_in_billing_retry_period'];
        }

        return $this;
    }

    /**
     * Product ID.
     *
     * @return string
     */
    public function getProductId(): string
    {
        return $this->product_id;
    }

    /**
     * Auto Renew Product ID.
     *
     * @return string
     */
    public function getAutoRenewProductId(): string
    {
        return $this->auto_renew_product_id;
    }

    /**
     * Auto Renew Status Code.
     *
     * @return bool
     */
    public function getAutoRenewStatus(): bool
    {
        return $this->auto_renew_status;
    }

    /**
     * Original Transaction ID.
     *
     * @return string
     */
    public function getOriginalTransactionId(): string
    {
        return $this->original_transaction_id;
    }

    /**
     * Expiration Intent Code.
     *
     * @return int|null
     */
    public function getExpirationIntent(): ?int
    {
        return $this->expiration_intent;
    }

    /**
     * Is In Billing Retry Period Code.
     *
     * @return int|null
     */
    public function isInBillingRetryPeriod(): ?int
    {
        return $this->is_in_billing_retry_period;
    }

    /*****************************************
     * Convenience methods
     *****************************************/

    /**
     * Status of Pending Renewal.
     *
     * This is a computed property that assumes a particular status based on
     * contextual information.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        // Active when no expiration intent
        if (null === $this->expiration_intent) {
            return $this::STATUS_ACTIVE;
        }

        // Pending when retrying
        if ($this::RETRY_PERIOD_ACTIVE === $this->is_in_billing_retry_period) {
            return $this::STATUS_PENDING;
        }

        // Expired when not retrying
        if ($this::RETRY_PERIOD_INACTIVE === $this->is_in_billing_retry_period) {
            return $this::STATUS_EXPIRED;
        }

        return null;
    }

    /**
     * Grace Period Expires Date.
     *
     * @return Carbon|null
     */
    public function getGracePeriodExpiresDate(): ?Carbon
    {
        return $this->grace_period_expires_date;
    }

    /**
     * Billing retrying and grace period expires date is in the future.
     *
     * @return bool
     */
    public function isInGracePeriod(): bool
    {
        return $this->is_in_billing_retry_period == self::RETRY_PERIOD_ACTIVE &&
            $this->grace_period_expires_date !== null &&
            $this->grace_period_expires_date->getTimestamp() > time();
    }

    /**
     * Update a key and reprocess object properties.
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
     * Get a value.
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
     * Unset a key.
     *
     * @param $key
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($key): void
    {
        unset($this->raw_data[$key]);
    }

    /**
     * Check if key exists.
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
