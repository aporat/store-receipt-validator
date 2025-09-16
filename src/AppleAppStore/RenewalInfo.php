<?php

namespace ReceiptValidator\AppleAppStore;

use Carbon\Carbon;
use DateTimeImmutable;

/**
 * Represents the decoded payload of a signedRenewalInfo token.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/jwsrenewalinfodecodedpayload
 */
class RenewalInfo
{
    /** @var array<string, mixed> */
    protected array $rawData = [];

    /** @var string|null */
    protected ?string $autoRenewProductId = null;
    /** @var bool|null */
    protected ?bool $autoRenewStatus = null;
    /** @var DateTimeImmutable|null */
    protected ?DateTimeImmutable $expirationIntentDate = null;
    /** @var bool|null */
    protected ?bool $isInBillingRetryPeriod = null;
    /** @var bool|null */
    protected ?bool $isUpgraded = null;
    /** @var string|null */
    protected ?string $originalTransactionId = null;
    /** @var int|null */
    protected ?int $priceConsentStatus = null;
    /** @var DateTimeImmutable|null */
    protected ?DateTimeImmutable $gracePeriodExpiresDate = null;
    /** @var int|null */
    protected ?int $renewalPrice = null;
    /** @var string|null */
    protected ?string $currency = null;
    /** @var string|null */
    protected ?string $offerIdentifier = null;
    /** @var int|null */
    protected ?int $offerType = null;
    /** @var string|null */
    protected ?string $offerDiscountType = null;
    /** @var string|null */
    protected ?string $offerPeriod = null;
    /** @var string|null */
    protected ?string $appTransactionId = null;
    /** @var string|null */
    protected ?string $appAccountToken = null;
    /** @var array<string>|null */
    protected ?array $eligibleWinBackOfferIds = null;
    /** @var DateTimeImmutable|null */
    protected ?DateTimeImmutable $signedDate = null;
    /** @var DateTimeImmutable|null */
    protected ?DateTimeImmutable $recentSubscriptionStartDate = null;
    /** @var DateTimeImmutable|null */
    protected ?DateTimeImmutable $renewalDate = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->rawData = $data;
        $this->parseData();
    }

    /**
     * @return $this
     */
    public function parseData(): self
    {
        $data = $this->rawData;

        $this->autoRenewProductId = $data['autoRenewProductId'] ?? null;

        // Correctly handle different boolean representations
        if (isset($data['autoRenewStatus'])) {
            $this->autoRenewStatus = is_bool($data['autoRenewStatus']) ? $data['autoRenewStatus'] : (bool)(int)$data['autoRenewStatus'];
        } else {
            $this->autoRenewStatus = null;
        }

        $this->originalTransactionId = $data['originalTransactionId'] ?? null;

        if (isset($data['isUpgraded'])) {
            $this->isUpgraded = is_bool($data['isUpgraded']) ? $data['isUpgraded'] : (bool)(int)$data['isUpgraded'];
        } else {
            $this->isUpgraded = null;
        }

        if (isset($data['isInBillingRetryPeriod'])) {
            $this->isInBillingRetryPeriod = is_bool($data['isInBillingRetryPeriod']) ? $data['isInBillingRetryPeriod'] : (bool)(int)$data['isInBillingRetryPeriod'];
        } else {
            $this->isInBillingRetryPeriod = null;
        }

        $this->priceConsentStatus = $data['priceConsentStatus'] ?? null;
        $this->renewalPrice = $data['renewalPrice'] ?? null;
        $this->currency = $data['currency'] ?? null;
        $this->offerIdentifier = $data['offerIdentifier'] ?? null;
        $this->offerType = $data['offerType'] ?? null;
        $this->offerDiscountType = $data['offerDiscountType'] ?? null;
        $this->offerPeriod = $data['offerPeriod'] ?? null;
        $this->appTransactionId = $data['appTransactionId'] ?? null;
        $this->appAccountToken = $data['appAccountToken'] ?? null;
        $this->eligibleWinBackOfferIds = $data['eligibleWinBackOfferIds'] ?? null;

        // Date parsing logic
        $this->expirationIntentDate = $this->parseDate($data['expirationIntentDate'] ?? null);
        $this->gracePeriodExpiresDate = $this->parseDate($data['gracePeriodExpiresDate'] ?? null);
        $this->signedDate = $this->parseDate($data['signedDate'] ?? null);
        $this->recentSubscriptionStartDate = $this->parseDate($data['recentSubscriptionStartDate'] ?? null);
        $this->renewalDate = $this->parseDate($data['renewalDate'] ?? null);

        return $this;
    }

    private function parseDate(?int $msTimestamp): ?DateTimeImmutable
    {
        if (is_null($msTimestamp)) {
            return null;
        }
        $timestampSeconds = (int)($msTimestamp / 1000);
        return (new DateTimeImmutable())->setTimestamp($timestampSeconds);
    }

    // Public Getters

    public function getAutoRenewProductId(): ?string
    {
        return $this->autoRenewProductId;
    }

    public function getAutoRenewStatus(): ?bool
    {
        return $this->autoRenewStatus;
    }

    public function getExpirationIntentDate(): ?Carbon
    {
        return $this->expirationIntentDate ? Carbon::instance($this->expirationIntentDate) : null;
    }

    public function isInBillingRetryPeriod(): ?bool
    {
        return $this->isInBillingRetryPeriod;
    }

    public function isUpgraded(): ?bool
    {
        return $this->isUpgraded;
    }

    public function getOriginalTransactionId(): ?string
    {
        return $this->originalTransactionId;
    }

    public function getPriceConsentStatus(): ?int
    {
        return $this->priceConsentStatus;
    }

    public function getGracePeriodExpiresDate(): ?Carbon
    {
        return $this->gracePeriodExpiresDate ? Carbon::instance($this->gracePeriodExpiresDate) : null;
    }

    public function getRenewalPrice(): ?int
    {
        return $this->renewalPrice;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getOfferIdentifier(): ?string
    {
        return $this->offerIdentifier;
    }

    public function getOfferType(): ?int
    {
        return $this->offerType;
    }

    public function getOfferDiscountType(): ?string
    {
        return $this->offerDiscountType;
    }

    public function getOfferPeriod(): ?string
    {
        return $this->offerPeriod;
    }

    public function getAppTransactionId(): ?string
    {
        return $this->appTransactionId;
    }

    public function getAppAccountToken(): ?string
    {
        return $this->appAccountToken;
    }

    /** @return string[]|null */
    public function getEligibleWinBackOfferIds(): ?array
    {
        return $this->eligibleWinBackOfferIds;
    }

    public function getSignedDate(): ?Carbon
    {
        return $this->signedDate ? Carbon::instance($this->signedDate) : null;
    }

    public function getRecentSubscriptionStartDate(): ?Carbon
    {
        return $this->recentSubscriptionStartDate ? Carbon::instance($this->recentSubscriptionStartDate) : null;
    }

    public function getRenewalDate(): ?Carbon
    {
        return $this->renewalDate ? Carbon::instance($this->renewalDate) : null;
    }
}
