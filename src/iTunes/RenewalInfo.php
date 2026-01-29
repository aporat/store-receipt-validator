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
 * @deprecated since version 2.0. Use {@see \ReceiptValidator\AppleAppStore\RenewalInfo} instead.
 *             Apple has deprecated the verifyReceipt endpoint in favor of the App Store Server API.
 * @link https://developer.apple.com/library/content/releasenotes/General/ValidateAppStoreReceipt/Chapters/ReceiptFields.html
 * @see https://developer.apple.com/documentation/appstoreserverapi
 */
final readonly class RenewalInfo extends AbstractRenewalInfo
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

    public string $productId;
    public ?string $autoRenewProductId;
    public ?string $originalTransactionId;

    public bool $autoRenewStatus;
    public bool $isInBillingRetryPeriod;

    /** @var int|null One of the EXPIRATION_INTENT_* codes */
    public ?int $expirationIntent;

    public ?CarbonImmutable $gracePeriodExpiresDate;

    /** @var array<string, mixed> */
    public array $rawData;

    /**
     * @param array<string, mixed>|null $data
     * @throws ValidationException
     */
    public function __construct(?array $data)
    {
        if (!is_array($data)) {
            throw new ValidationException('Response must be an array');
        }

        $this->rawData = $data;

        $this->productId             = (string) $this->toString($data, 'product_id', '');
        $this->originalTransactionId = $this->toString($data, 'original_transaction_id', '');
        $this->autoRenewProductId    = $this->toString($data, 'auto_renew_product_id', '');

        $this->autoRenewStatus        = $this->toBool($data, 'auto_renew_status');
        $this->isInBillingRetryPeriod = $this->toBool($data, 'is_in_billing_retry_period');

        $this->expirationIntent       = $this->toInt($data, 'expiration_intent');
        $this->gracePeriodExpiresDate = $this->toDateFromMs($data, 'grace_period_expires_date_ms');
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
