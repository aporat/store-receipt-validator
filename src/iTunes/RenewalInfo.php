<?php

namespace ReceiptValidator\iTunes;

use Carbon\Carbon;
use ReceiptValidator\AbstractRenewalInfo;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Represents the renewal info section of the iTunes receipt.
 *
 * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
 */
class RenewalInfo extends AbstractRenewalInfo
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

    /** @var string */
    public const string STATUS_PENDING = 'pending';

    /** @var string */
    public const string STATUS_EXPIRED = 'expired';

    protected string $productId = '';
    protected string $autoRenewProductId = '';
    protected string $originalTransactionId = '';
    protected bool $autoRenewStatus = false;
    protected ?int $expirationIntent = null;
    protected ?Carbon $gracePeriodExpiresDate = null;
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

        if (!is_array($this->rawData)) {
            throw new ValidationException('Response must be a scalar value');
        }

        $this->productId = (string) ($this->rawData['product_id'] ?? '');
        $this->originalTransactionId = (string) ($this->rawData['original_transaction_id'] ?? '');
        $this->autoRenewProductId = (string) ($this->rawData['auto_renew_product_id'] ?? '');
        $this->autoRenewStatus = (bool) ((int) ($this->rawData['auto_renew_status'] ?? false));

        $this->expirationIntent = array_key_exists('expiration_intent', $this->rawData)
            ? (int) $this->rawData['expiration_intent']
            : null;

        if (!empty($data['grace_period_expires_date_ms'])) {
            $this->gracePeriodExpiresDate = Carbon::createFromTimestampMs($data['grace_period_expires_date_ms']);
        }

        $this->isInBillingRetryPeriod = array_key_exists('is_in_billing_retry_period', $this->rawData)
            ? (int) $this->rawData['is_in_billing_retry_period']
            : null;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getAutoRenewProductId(): string
    {
        return $this->autoRenewProductId;
    }

    public function getAutoRenewStatus(): bool
    {
        return $this->autoRenewStatus;
    }

    public function getOriginalTransactionId(): string
    {
        return $this->originalTransactionId;
    }

    public function getExpirationIntent(): ?int
    {
        return $this->expirationIntent;
    }

    public function isInBillingRetryPeriod(): ?int
    {
        return $this->isInBillingRetryPeriod;
    }

    public function getStatus(): ?string
    {
        if ($this->autoRenewStatus === false) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isInBillingRetryPeriod === self::RETRY_PERIOD_ACTIVE) {
            return self::STATUS_PENDING;
        }

        if ($this->expirationIntent !== null) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }

    public function getGracePeriodExpiresDate(): ?Carbon
    {
        return $this->gracePeriodExpiresDate;
    }

    public function isInGracePeriod(): bool
    {
        if (
            $this->isInBillingRetryPeriod !== self::RETRY_PERIOD_ACTIVE ||
            $this->gracePeriodExpiresDate === null
        ) {
            return false;
        }

        return $this->gracePeriodExpiresDate->getTimestamp() > time();
    }
}
