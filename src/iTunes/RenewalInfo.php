<?php

declare(strict_types=1);

namespace ReceiptValidator\iTunes;

use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use ReceiptValidator\AbstractRenewalInfo;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Represents the renewal info section of the iTunes receipt.
 *
 * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
 */
final class RenewalInfo extends AbstractRenewalInfo
{
    // Expiration Intent Codes
    public const int EXPIRATION_INTENT_CANCELLED           = 1;
    public const int EXPIRATION_INTENT_BILLING_ERROR       = 2;
    public const int EXPIRATION_INTENT_INCREASE_DECLINED   = 3;
    public const int EXPIRATION_INTENT_PRODUCT_UNAVAILABLE = 4;
    public const int EXPIRATION_INTENT_UNKNOWN             = 5;

    public const string STATUS_ACTIVE  = 'active';
    public const string STATUS_PENDING = 'pending';
    public const string STATUS_EXPIRED = 'expired';

    protected string $productId;
    protected ?string $autoRenewProductId = null;
    protected ?string $originalTransactionId = null;

    protected bool $autoRenewStatus = false;
    protected bool $isInBillingRetryPeriod = false;

    /** @var int|null One of the EXPIRATION_INTENT_* codes */
    protected ?int $expirationIntent = null;

    protected ?CarbonImmutable $gracePeriodExpiresDate = null;

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
            throw new ValidationException('Response must be an array');
        }

        $this->productId             = (string) $this->toString($this->rawData, 'product_id', '');
        $this->originalTransactionId = $this->toString($this->rawData, 'original_transaction_id', '');
        $this->autoRenewProductId    = $this->toString($this->rawData, 'auto_renew_product_id', '');

        $this->autoRenewStatus        = $this->toBool($this->rawData, 'auto_renew_status', false);
        $this->isInBillingRetryPeriod = $this->toBool($this->rawData, 'is_in_billing_retry_period', false);

        $this->expirationIntent       = $this->toInt($this->rawData, 'expiration_intent');
        $this->gracePeriodExpiresDate = $this->toDateFromMs($this->rawData, 'grace_period_expires_date_ms');
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getAutoRenewProductId(): ?string
    {
        return $this->autoRenewProductId;
    }

    public function getAutoRenewStatus(): bool
    {
        return $this->autoRenewStatus;
    }

    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }

    /**
     * Raw expiration intent code (or null if none).
     */
    public function getExpirationIntent(): ?int
    {
        return $this->expirationIntent;
    }

    /**
     * Human-readable explanation of the expiration intent.
     */
    public function getExpirationReason(): ?string
    {
        return match ($this->expirationIntent) {
            self::EXPIRATION_INTENT_CANCELLED           => 'Customer canceled subscription',
            self::EXPIRATION_INTENT_BILLING_ERROR       => 'Billing error (e.g. payment declined)',
            self::EXPIRATION_INTENT_INCREASE_DECLINED   => 'Price increase not accepted',
            self::EXPIRATION_INTENT_PRODUCT_UNAVAILABLE => 'Product no longer available',
            self::EXPIRATION_INTENT_UNKNOWN             => 'Unknown reason',
            default                                     => null,
        };
    }

    public function hasExpirationIntent(): bool
    {
        return $this->expirationIntent !== null;
    }

    public function isInBillingRetryPeriod(): bool
    {
        return $this->isInBillingRetryPeriod;
    }

    public function getStatus(): string
    {
        if (!$this->autoRenewStatus) {
            return self::STATUS_EXPIRED;
        }

        if ($this->isInBillingRetryPeriod) {
            return self::STATUS_PENDING;
        }

        if ($this->expirationIntent !== null) {
            return self::STATUS_EXPIRED;
        }

        return self::STATUS_ACTIVE;
    }

    public function getGracePeriodExpiresDate(): ?CarbonInterface
    {
        return $this->gracePeriodExpiresDate;
    }

    public function isInGracePeriod(): bool
    {
        if (!$this->isInBillingRetryPeriod || $this->gracePeriodExpiresDate === null) {
            return false;
        }

        return $this->gracePeriodExpiresDate->getTimestamp() > time();
    }
}
