<?php

namespace ReceiptValidator\iTunes;

use ArrayAccess;
use Carbon\Carbon;
use ReceiptValidator\Exceptions\ValidationException;
use ReturnTypeWillChange;

/**
 * Represents the renewal info section of the iTunes receipt.
 *
 * @implements ArrayAccess<string, mixed>
 * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
 */
class RenewalInfo implements ArrayAccess
{
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

    /**
     * @var string
     */
    protected string $productId = '';

    /**
     * @var string
     */
    protected string $autoRenewProductId = '';

    /**
     * @var string
     */
    protected string $originalTransactionId = '';

    /**
     * @var bool
     */
    protected bool $autoRenewStatus = false;

    /**
     * @var int|null
     */
    protected ?int $expirationIntent = null;

    /**
     * @var Carbon|null
     */
    protected ?Carbon $gracePeriodExpiresDate = null;

    /**
     * @var int|null
     */
    protected ?int $isInBillingRetryPeriod = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $rawData = null;

    /**
     * @param array<string, mixed>|null $data
     * @throws ValidationException
     */
    public function __construct(?array $data)
    {
        $this->rawData = $data;
        $this->parseData();
    }

    /**
     * @return $this
     * @throws ValidationException
     */
    public function parseData(): self
    {
        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be a scalar value');
        }

        $this->productId = $this->rawData['product_id'] ?? '';
        $this->originalTransactionId = $this->rawData['original_transaction_id'] ?? '';
        $this->autoRenewProductId = $this->rawData['auto_renew_product_id'] ?? '';
        $this->autoRenewStatus = (bool)($this->rawData['auto_renew_status'] ?? false);

        if (array_key_exists('expiration_intent', $this->rawData)) {
            $this->expirationIntent = (int)$this->rawData['expiration_intent'];
        }

        if (array_key_exists('grace_period_expires_date_ms', $this->rawData)) {
            $this->gracePeriodExpiresDate = Carbon::createFromTimestampUTC(
                (int)round($this->rawData['grace_period_expires_date_ms'] / 1000)
            );
        }

        if (array_key_exists('is_in_billing_retry_period', $this->rawData)) {
            $this->isInBillingRetryPeriod = (int)$this->rawData['is_in_billing_retry_period'];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @return string
     */
    public function getAutoRenewProductId(): string
    {
        return $this->autoRenewProductId;
    }

    /**
     * @return bool
     */
    public function getAutoRenewStatus(): bool
    {
        return $this->autoRenewStatus;
    }

    /**
     * @return string
     */
    public function getOriginalTransactionId(): string
    {
        return $this->originalTransactionId;
    }

    /**
     * @return int|null
     */
    public function getExpirationIntent(): ?int
    {
        return $this->expirationIntent;
    }

    /**
     * @return int|null
     */
    public function isInBillingRetryPeriod(): ?int
    {
        return $this->isInBillingRetryPeriod;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        if ($this->expirationIntent === null) {
            return self::STATUS_ACTIVE;
        }
        if ($this->isInBillingRetryPeriod === self::RETRY_PERIOD_ACTIVE) {
            return self::STATUS_PENDING;
        }
        if ($this->isInBillingRetryPeriod === self::RETRY_PERIOD_INACTIVE) {
            return self::STATUS_EXPIRED;
        }
        return null;
    }

    /**
     * @return Carbon|null
     */
    public function getGracePeriodExpiresDate(): ?Carbon
    {
        return $this->gracePeriodExpiresDate;
    }

    /**
     * @return bool
     */
    public function isInGracePeriod(): bool
    {
        return $this->isInBillingRetryPeriod === self::RETRY_PERIOD_ACTIVE
            && $this->gracePeriodExpiresDate !== null
            && $this->gracePeriodExpiresDate->getTimestamp() > time();
    }

    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        $this->rawData[$offset] = $value;
        $this->parseData();
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
