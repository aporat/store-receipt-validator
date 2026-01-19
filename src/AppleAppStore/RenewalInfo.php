<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractRenewalInfo;

/**
 * Represents the decoded payload of a signedRenewalInfo token.
 *
 * @see https://developer.apple.com/documentation/appstoreservernotifications/jwsrenewalinfodecodedpayload
 */
class RenewalInfo extends AbstractRenewalInfo
{
    /** The product identifier of the auto-renewable subscription. */
    protected ?string $autoRenewProductId = null;

    /** The renewal status of the subscription. */
    protected bool $autoRenewStatus = false;

    /** The time when the subscription will expire due to a billing issue or other reason. */
    protected ?CarbonImmutable $expirationIntentDate = null;

    /** Indicates whether the App Store is attempting to renew the subscription. */
    protected bool $isInBillingRetryPeriod = false;

    /** Indicates whether the user upgraded to another subscription. */
    protected bool $isUpgraded = false;

    /** The original transaction identifier of the subscription. */
    protected ?string $originalTransactionId = null;

    /**
     * The status of the customer’s consent to a subscription price increase.
     * 0 = customer has not responded, 1 = customer consented
     */
    protected ?int $priceConsentStatus = null;

    /** The time when the subscription's billing grace period expires. */
    protected ?CarbonImmutable $gracePeriodExpiresDate = null;

    /** The subscription price in milli-units (e.g., cents). */
    protected ?int $renewalPrice = null;

    /** The currency code for the renewal price. */
    protected ?string $currency = null;

    /** The identifier of the promotional offer. */
    protected ?string $offerIdentifier = null;

    /** The type of offer being applied. */
    protected ?int $offerType = null;

    /** The discount type of the offer. */
    protected ?string $offerDiscountType = null;

    /** The ISO 8601 duration of the offer period. */
    protected ?string $offerPeriod = null;

    /** The App Store transaction ID of the renewal. */
    protected ?string $appTransactionId = null;

    /** The app account token that uniquely identifies the customer. */
    protected ?string $appAccountToken = null;

    /** The list of eligible win-back offer identifiers. */
    /** @var list<string> */
    protected ?array $eligibleWinBackOfferIds = null;

    /** The signed date of this renewal info token. */
    protected ?CarbonImmutable $signedDate = null;

    /** The start date of the most recent subscription. */
    protected ?CarbonImmutable $recentSubscriptionStartDate = null;

    /** The next renewal date. */
    protected ?CarbonImmutable $renewalDate = null;

    /** @var array<string, mixed>|null */
    protected ?array $rawData = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->rawData = $data;

        $this->autoRenewProductId       = $this->toString($data, 'autoRenewProductId');
        $this->autoRenewStatus          = $this->toBool($data, 'autoRenewStatus');
        $this->originalTransactionId    = $this->toString($data, 'originalTransactionId');
        $this->isUpgraded               = $this->toBool($data, 'isUpgraded');
        $this->isInBillingRetryPeriod   = $this->toBool($data, 'isInBillingRetryPeriod');
        $this->priceConsentStatus       = $this->toInt($data, 'priceConsentStatus');
        $this->renewalPrice             = $this->toInt($data, 'renewalPrice');
        $this->currency                 = $this->toString($data, 'currency');
        $this->offerIdentifier          = $this->toString($data, 'offerIdentifier');
        $this->offerType                = $this->toInt($data, 'offerType');
        $this->offerDiscountType        = $this->toString($data, 'offerDiscountType');
        $this->offerPeriod              = $this->toString($data, 'offerPeriod');
        $this->appTransactionId         = $this->toString($data, 'appTransactionId');
        $this->appAccountToken          = $this->toString($data, 'appAccountToken');

        $eligible = $data['eligibleWinBackOfferIds'] ?? null;
        $this->eligibleWinBackOfferIds = is_array($eligible) ? array_values(array_map('strval', $eligible)) : null;

        $this->expirationIntentDate       = $this->toDateFromMs($data, 'expirationIntentDate');
        $this->gracePeriodExpiresDate     = $this->toDateFromMs($data, 'gracePeriodExpiresDate');
        $this->signedDate                 = $this->toDateFromMs($data, 'signedDate');
        $this->recentSubscriptionStartDate = $this->toDateFromMs($data, 'recentSubscriptionStartDate');
        $this->renewalDate                = $this->toDateFromMs($data, 'renewalDate');
    }

    public function getAutoRenewProductId(): ?string
    {
        return $this->autoRenewProductId;
    }
    public function getAutoRenewStatus(): bool
    {
        return $this->autoRenewStatus;
    }

    /** @return CarbonInterface|null */
    public function getExpirationIntentDate(): ?CarbonInterface
    {
        return $this->expirationIntentDate;
    }

    public function isInBillingRetryPeriod(): bool
    {
        return $this->isInBillingRetryPeriod;
    }
    public function isUpgraded(): bool
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

    /** @return CarbonInterface|null */
    public function getGracePeriodExpiresDate(): ?CarbonInterface
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

    /** @return string[]|null */
    public function getEligibleWinBackOfferIds(): ?array
    {
        return $this->eligibleWinBackOfferIds;
    }

    /** @return CarbonInterface|null */
    public function getSignedDate(): ?CarbonInterface
    {
        return $this->signedDate;
    }

    /** @return CarbonInterface|null */
    public function getRecentSubscriptionStartDate(): ?CarbonInterface
    {
        return $this->recentSubscriptionStartDate;
    }

    /** @return CarbonInterface|null */
    public function getRenewalDate(): ?CarbonInterface
    {
        return $this->renewalDate;
    }
}
