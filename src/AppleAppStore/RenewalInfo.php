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
final readonly class RenewalInfo extends AbstractRenewalInfo
{
    /** The product identifier of the auto-renewable subscription. */
    public ?string $autoRenewProductId;

    /** The renewal status of the subscription. */
    public bool $autoRenewStatus;

    /** The time when the subscription will expire due to a billing issue or other reason. */
    public ?CarbonImmutable $expirationIntentDate;

    /** Indicates whether the App Store is attempting to renew the subscription. */
    public bool $isInBillingRetryPeriod;

    /** Indicates whether the user upgraded to another subscription. */
    public bool $isUpgraded;

    /** The original transaction identifier of the subscription. */
    public ?string $originalTransactionId;

    /**
     * The status of the customer's consent to a subscription price increase.
     * 0 = customer has not responded, 1 = customer consented
     */
    public ?int $priceConsentStatus;

    /** The time when the subscription's billing grace period expires. */
    public ?CarbonImmutable $gracePeriodExpiresDate;

    /** The subscription price in milli-units (e.g., cents). */
    public ?int $renewalPrice;

    /** The currency code for the renewal price. */
    public ?string $currency;

    /** The identifier of the promotional offer. */
    public ?string $offerIdentifier;

    /** The type of offer being applied. */
    public ?int $offerType;

    /** The discount type of the offer. */
    public ?string $offerDiscountType;

    /** The ISO 8601 duration of the offer period. */
    public ?string $offerPeriod;

    /** The App Store transaction ID of the renewal. */
    public ?string $appTransactionId;

    /** The app account token that uniquely identifies the customer. */
    public ?string $appAccountToken;

    /**
     * The list of eligible win-back offer identifiers.
     *
     * @var list<string>|null
     */
    public ?array $eligibleWinBackOfferIds;

    /** The signed date of this renewal info token. */
    public ?CarbonImmutable $signedDate;

    /** The start date of the most recent subscription. */
    public ?CarbonImmutable $recentSubscriptionStartDate;

    /** The next renewal date. */
    public ?CarbonImmutable $renewalDate;

    /** @var array<string, mixed> */
    public array $rawData;

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

        $this->expirationIntentDate        = $this->toDateFromMs($data, 'expirationIntentDate');
        $this->gracePeriodExpiresDate      = $this->toDateFromMs($data, 'gracePeriodExpiresDate');
        $this->signedDate                  = $this->toDateFromMs($data, 'signedDate');
        $this->recentSubscriptionStartDate = $this->toDateFromMs($data, 'recentSubscriptionStartDate');
        $this->renewalDate                 = $this->toDateFromMs($data, 'renewalDate');
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
