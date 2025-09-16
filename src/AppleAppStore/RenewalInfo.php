<?php

namespace ReceiptValidator\AppleAppStore;

use Carbon\Carbon;
use ReceiptValidator\AbstractRenewalInfo;

/**
 * Represents the decoded payload of a signedRenewalInfo token.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/jwsrenewalinfodecodedpayload
 */
class RenewalInfo extends AbstractRenewalInfo
{
    /**
     * The product identifier of the auto-renewable subscription.
     *
     * @var string|null
     */
    protected ?string $autoRenewProductId = null;

    /**
     * The renewal status of the subscription.
     *
     * @var bool|null
     */
    protected ?bool $autoRenewStatus = null;

    /**
     * The time when the subscription will expire due to a billing issue or other reason.
     *
     * @var Carbon|null
     */
    protected ?Carbon $expirationIntentDate = null;

    /**
     * Indicates whether the App Store is attempting to renew the subscription.
     *
     * @var bool|null
     */
    protected ?bool $isInBillingRetryPeriod = null;

    /**
     * Indicates whether the user upgraded to another subscription.
     *
     * @var bool|null
     */
    protected ?bool $isUpgraded = null;

    /**
     * The original transaction identifier of the subscription.
     *
     * @var string|null
     */
    protected ?string $originalTransactionId = null;

    /**
     * The status of the customer’s consent to a subscription price increase.
     * 0 = customer has not responded, 1 = customer consented
     *
     * @var int|null
     */
    protected ?int $priceConsentStatus = null;

    /**
     * The time when the subscription's billing grace period expires.
     *
     * @var Carbon|null
     */
    protected ?Carbon $gracePeriodExpiresDate = null;

    /**
     * The subscription price in milli-units (e.g., cents).
     *
     * @var int|null
     */
    protected ?int $renewalPrice = null;

    /**
     * The currency code for the renewal price.
     *
     * @var string|null
     */
    protected ?string $currency = null;

    /**
     * The identifier of the promotional offer.
     *
     * @var string|null
     */
    protected ?string $offerIdentifier = null;

    /**
     * The type of offer being applied.
     *
     * @var int|null
     */
    protected ?int $offerType = null;

    /**
     * The discount type of the offer.
     *
     * @var string|null
     */
    protected ?string $offerDiscountType = null;

    /**
     * The ISO 8601 duration of the offer period.
     *
     * @var string|null
     */
    protected ?string $offerPeriod = null;

    /**
     * The App Store transaction ID of the renewal.
     *
     * @var string|null
     */
    protected ?string $appTransactionId = null;

    /**
     * The app account token that uniquely identifies the customer.
     *
     * @var string|null
     */
    protected ?string $appAccountToken = null;

    /**
     * The list of eligible win-back offer identifiers.
     *
     * @var string[]|null
     */
    protected ?array $eligibleWinBackOfferIds = null;

    /**
     * The signed date of this renewal info token.
     *
     * @var Carbon|null
     */
    protected ?Carbon $signedDate = null;

    /**
     * The start date of the most recent subscription.
     *
     * @var Carbon|null
     */
    protected ?Carbon $recentSubscriptionStartDate = null;

    /**
     * The next renewal date.
     *
     * @var Carbon|null
     */
    protected ?Carbon $renewalDate = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $rawData = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->rawData = $data;

        $this->autoRenewProductId = $data['autoRenewProductId'] ?? null;
        $this->autoRenewStatus = isset($data['autoRenewStatus']) ? (bool) $data['autoRenewStatus'] : null;
        $this->originalTransactionId = $data['originalTransactionId'] ?? null;
        $this->isUpgraded = isset($data['isUpgraded']) ? (bool) $data['isUpgraded'] : null;
        $this->isInBillingRetryPeriod = isset($data['isInBillingRetryPeriod']) ? (bool) $data['isInBillingRetryPeriod'] : null;
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

        if (!empty($data['expirationIntentDate'])) {
            $this->expirationIntentDate = Carbon::createFromTimestampMs($data['expirationIntentDate']);
        }

        if (!empty($data['gracePeriodExpiresDate'])) {
            $this->gracePeriodExpiresDate = Carbon::createFromTimestampMs($data['gracePeriodExpiresDate']);
        }

        if (!empty($data['signedDate'])) {
            $this->signedDate = Carbon::createFromTimestampMs($data['signedDate']);
        }

        if (!empty($data['recentSubscriptionStartDate'])) {
            $this->recentSubscriptionStartDate = Carbon::createFromTimestampMs($data['recentSubscriptionStartDate']);
        }

        if (!empty($data['renewalDate'])) {
            $this->renewalDate = Carbon::createFromTimestampMs($data['renewalDate']);
        }
    }

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
        return $this->expirationIntentDate;
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
        return $this->gracePeriodExpiresDate;
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

    /**
     * @return string[]|null
     */
    public function getEligibleWinBackOfferIds(): ?array
    {
        return $this->eligibleWinBackOfferIds;
    }

    public function getSignedDate(): ?Carbon
    {
        return $this->signedDate;
    }

    public function getRecentSubscriptionStartDate(): ?Carbon
    {
        return $this->recentSubscriptionStartDate;
    }

    public function getRenewalDate(): ?Carbon
    {
        return $this->renewalDate;
    }
}
