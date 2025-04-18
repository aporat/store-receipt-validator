<?php

namespace ReceiptValidator\iTunes;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

class RenewalInfo implements ArrayAccess
{
    /**
     * Developer friendly field codes
     * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
     */

    // Expiration Intent Codes
    public const int EXPIRATION_INTENT_CANCELLED = 1;
    public const int EXPIRATION_INTENT_BILLING_ERROR = 2;
    public const int EXPIRATION_INTENT_INCREASE_DECLINED = 3;
    public const int EXPIRATION_INTENT_PRODUCT_UNAVAILABLE = 4;
    public const int EXPIRATION_INTENT_UNKNOWN = 5;

    // Retry flag codes
    public const int RETRY_PERIOD_ACTIVE = 1;
    public const int RETRY_PERIOD_INACTIVE = 0;

    // Auto-renew status codes
    public const int AUTO_RENEW_ACTIVE = 1;
    public const int AUTO_RENEW_INACTIVE = 0;

    /** @var string */
    public const string STATUS_ACTIVE = 'active';
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_EXPIRED = 'expired';

    protected string $product_id = '';
    protected string $auto_renew_product_id = '';
    protected string $original_transaction_id = '';
    protected bool $auto_renew_status = false;
    protected ?int $expiration_intent = null;
    protected ?Carbon $grace_period_expires_date = null;
    protected ?int $is_in_billing_retry_period = null;
    protected ?array $raw_data = null;

    /**
     * @param array|null $data
     * @throws ValidationException
     */
    public function __construct(?array $data)
    {
        $this->raw_data = $data;
        $this->parseData();
    }

    /**
     * @return $this
     * @throws ValidationException
     */
    public function parseData(): self
    {
        if (!is_array($this->raw_data)) {
            throw new ValidationException('Response must be a scalar value');
        }

        $this->product_id = $this->raw_data['product_id'] ?? '';
        $this->original_transaction_id = $this->raw_data['original_transaction_id'] ?? '';
        $this->auto_renew_product_id = $this->raw_data['auto_renew_product_id'] ?? '';
        $this->auto_renew_status = (bool)($this->raw_data['auto_renew_status'] ?? false);

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

    public function getProductId(): string
    {
        return $this->product_id;
    }

    public function getAutoRenewProductId(): string
    {
        return $this->auto_renew_product_id;
    }

    public function getAutoRenewStatus(): bool
    {
        return $this->auto_renew_status;
    }

    public function getOriginalTransactionId(): string
    {
        return $this->original_transaction_id;
    }

    public function getExpirationIntent(): ?int
    {
        return $this->expiration_intent;
    }

    public function isInBillingRetryPeriod(): ?int
    {
        return $this->is_in_billing_retry_period;
    }

    public function getStatus(): ?string
    {
        if ($this->expiration_intent === null) {
            return self::STATUS_ACTIVE;
        }
        if ($this->is_in_billing_retry_period === self::RETRY_PERIOD_ACTIVE) {
            return self::STATUS_PENDING;
        }
        if ($this->is_in_billing_retry_period === self::RETRY_PERIOD_INACTIVE) {
            return self::STATUS_EXPIRED;
        }
        return null;
    }

    public function getGracePeriodExpiresDate(): ?Carbon
    {
        return $this->grace_period_expires_date;
    }

    public function isInGracePeriod(): bool
    {
        return $this->is_in_billing_retry_period === self::RETRY_PERIOD_ACTIVE
            && $this->grace_period_expires_date !== null
            && $this->grace_period_expires_date->getTimestamp() > time();
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
