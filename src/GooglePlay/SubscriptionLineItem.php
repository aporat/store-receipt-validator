<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * One item of a subscription purchase: a single subscription product and its plan.
 *
 * A `subscriptionsv2` purchase carries one line item per product; multi-product
 * purchases (add-ons) carry several. The transaction ID exposed via the base class
 * is the line item's latest successful order ID.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2#subscriptionpurchaselineitem
 */
final readonly class SubscriptionLineItem extends AbstractTransaction
{
    /** When the entitlement for this line item expires. */
    public ?CarbonImmutable $expiryTime;

    /** The order ID of the most recent successful charge for this item. */
    public ?string $latestSuccessfulOrderId;

    /** Whether this item is on an auto-renewing plan. */
    public bool $isAutoRenewingPlan;

    /** Whether the auto-renewing plan will renew at expiry (false once the user cancels). */
    public bool $autoRenewEnabled;

    /** Whether this item is on a prepaid plan. */
    public bool $isPrepaidPlan;

    /** For prepaid plans, the time after which the user may top up. */
    public ?CarbonImmutable $prepaidAllowExtendAfterTime;

    /** The base plan the user is on. */
    public ?string $basePlanId;

    /** The offer the user redeemed, if any. */
    public ?string $offerId;

    /**
     * Tags attached to the redeemed offer.
     *
     * @var array<int, string>
     */
    public array $offerTags;

    /** Which phase of the offer the user is in (free trial, intro price, base price). */
    public SubscriptionOfferPhase $offerPhase;

    /** For a proration period, the phase the user was in before the plan change. */
    public ?string $prorationOriginalOfferPhase;

    /** The product this item will be replaced with at the next renewal, if a deferred replacement is pending. */
    public ?string $deferredItemReplacementProductId;

    /** True when this item will be removed at the next renewal. */
    public bool $deferredItemRemoval;

    /** The item this one replaced (or is replacing), when the purchase was an upgrade or downgrade. */
    public ?ItemReplacement $itemReplacement;

    /** The recurring price of an auto-renewing plan, if present. */
    public ?Money $recurringPrice;

    /** Pending or applied price change on an auto-renewing plan, if present. */
    public ?PriceChangeDetails $priceChangeDetails;

    /** Installment commitment details, for installment plans. */
    public ?InstallmentPlan $installmentPlan;

    /** A price step-up awaiting or holding consent, if any. */
    public ?PriceStepUpConsentDetails $priceStepUpConsentDetails;

    /** The kind of promotion code redeemed at signup, if any. */
    public ?SignupPromotionType $signupPromotionType;

    /** The vanity code redeemed at signup, when the promotion was a vanity code. */
    public ?string $promotionCode;

    /**
     * @param array<string, mixed> $data A single entry from `lineItems`.
     */
    public function __construct(array $data = [])
    {
        $latestOrderId = $this->toString($data, 'latestSuccessfulOrderId');

        parent::__construct(
            rawData: $data,
            quantity: 1,
            productId: $this->toString($data, 'productId'),
            transactionId: $latestOrderId,
        );

        $this->latestSuccessfulOrderId = $latestOrderId;
        $this->expiryTime              = $this->toDateFromRfc3339($data, 'expiryTime');

        $autoRenewing = is_array($data['autoRenewingPlan'] ?? null) ? $data['autoRenewingPlan'] : null;
        $prepaid      = is_array($data['prepaidPlan'] ?? null) ? $data['prepaidPlan'] : null;
        $offer        = is_array($data['offerDetails'] ?? null) ? $data['offerDetails'] : [];
        $deferred     = is_array($data['deferredItemReplacement'] ?? null) ? $data['deferredItemReplacement'] : [];
        $replacement  = is_array($data['itemReplacement'] ?? null) ? $data['itemReplacement'] : null;
        $phase        = is_array($data['offerPhase'] ?? null) ? $data['offerPhase'] : [];
        $promotion    = is_array($data['signupPromotion'] ?? null) ? $data['signupPromotion'] : [];

        $this->isAutoRenewingPlan = $autoRenewing !== null;
        $this->autoRenewEnabled   = $autoRenewing !== null && $this->toBool($autoRenewing, 'autoRenewEnabled');
        $this->recurringPrice     = Money::fromArray($autoRenewing['recurringPrice'] ?? null);

        $priceChange              = is_array($autoRenewing['priceChangeDetails'] ?? null) ? $autoRenewing['priceChangeDetails'] : null;
        $this->priceChangeDetails = $priceChange !== null ? new PriceChangeDetails($priceChange) : null;

        $installment           = is_array($autoRenewing['installmentDetails'] ?? null) ? $autoRenewing['installmentDetails'] : null;
        $this->installmentPlan = $installment !== null ? new InstallmentPlan($installment) : null;

        $stepUp                          = is_array($autoRenewing['priceStepUpConsentDetails'] ?? null) ? $autoRenewing['priceStepUpConsentDetails'] : null;
        $this->priceStepUpConsentDetails = $stepUp !== null ? new PriceStepUpConsentDetails($stepUp) : null;

        $this->isPrepaidPlan               = $prepaid !== null;
        $this->prepaidAllowExtendAfterTime = $prepaid !== null ? $this->toDateFromRfc3339($prepaid, 'allowExtendAfterTime') : null;

        $this->basePlanId = $this->toString($offer, 'basePlanId');
        $this->offerId    = $this->toString($offer, 'offerId');
        $this->offerTags  = array_values(array_map('strval', is_array($offer['offerTags'] ?? null) ? $offer['offerTags'] : []));

        $this->offerPhase = match (true) {
            isset($phase['freeTrial'])         => SubscriptionOfferPhase::FREE_TRIAL,
            isset($phase['introductoryPrice']) => SubscriptionOfferPhase::INTRODUCTORY_PRICE,
            isset($phase['basePrice'])         => SubscriptionOfferPhase::BASE_PRICE,
            isset($phase['prorationPeriod'])   => SubscriptionOfferPhase::PRORATION_PERIOD,
            default                            => SubscriptionOfferPhase::UNSPECIFIED,
        };
        $proration                         = is_array($phase['prorationPeriod'] ?? null) ? $phase['prorationPeriod'] : [];
        $this->prorationOriginalOfferPhase = $this->toString($proration, 'originalOfferPhaseType');

        $this->deferredItemReplacementProductId = $this->toString($deferred, 'productId');
        $this->deferredItemRemoval              = array_key_exists('deferredItemRemoval', $data) && $data['deferredItemRemoval'] !== null;
        $this->itemReplacement                  = $replacement !== null ? new ItemReplacement($replacement) : null;

        $this->signupPromotionType = match (true) {
            isset($promotion['oneTimeCode']) => SignupPromotionType::ONE_TIME_CODE,
            isset($promotion['vanityCode'])  => SignupPromotionType::VANITY_CODE,
            default                          => null,
        };
        $vanity              = is_array($promotion['vanityCode'] ?? null) ? $promotion['vanityCode'] : [];
        $this->promotionCode = $this->toString($vanity, 'promotionCode');
    }

    public function getExpiryTime(): ?CarbonInterface
    {
        return $this->expiryTime;
    }

    public function getLatestSuccessfulOrderId(): ?string
    {
        return $this->latestSuccessfulOrderId;
    }

    public function isAutoRenewingPlan(): bool
    {
        return $this->isAutoRenewingPlan;
    }

    public function isAutoRenewEnabled(): bool
    {
        return $this->autoRenewEnabled;
    }

    public function isPrepaidPlan(): bool
    {
        return $this->isPrepaidPlan;
    }

    public function getPrepaidAllowExtendAfterTime(): ?CarbonInterface
    {
        return $this->prepaidAllowExtendAfterTime;
    }

    public function getBasePlanId(): ?string
    {
        return $this->basePlanId;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    /** @return array<int, string> */
    public function getOfferTags(): array
    {
        return $this->offerTags;
    }

    public function getOfferPhase(): SubscriptionOfferPhase
    {
        return $this->offerPhase;
    }

    public function isInFreeTrial(): bool
    {
        return $this->offerPhase === SubscriptionOfferPhase::FREE_TRIAL;
    }

    public function isInIntroductoryPrice(): bool
    {
        return $this->offerPhase === SubscriptionOfferPhase::INTRODUCTORY_PRICE;
    }

    public function getProrationOriginalOfferPhase(): ?string
    {
        return $this->prorationOriginalOfferPhase;
    }

    public function getDeferredItemReplacementProductId(): ?string
    {
        return $this->deferredItemReplacementProductId;
    }

    public function hasDeferredItemRemoval(): bool
    {
        return $this->deferredItemRemoval;
    }

    public function getItemReplacement(): ?ItemReplacement
    {
        return $this->itemReplacement;
    }

    public function getRecurringPrice(): ?Money
    {
        return $this->recurringPrice;
    }

    public function getPriceChangeDetails(): ?PriceChangeDetails
    {
        return $this->priceChangeDetails;
    }

    public function getInstallmentPlan(): ?InstallmentPlan
    {
        return $this->installmentPlan;
    }

    public function getPriceStepUpConsentDetails(): ?PriceStepUpConsentDetails
    {
        return $this->priceStepUpConsentDetails;
    }

    public function getSignupPromotionType(): ?SignupPromotionType
    {
        return $this->signupPromotionType;
    }

    public function getPromotionCode(): ?string
    {
        return $this->promotionCode;
    }

    /**
     * Whether this item's entitlement is still in the future.
     */
    public function isExpired(?CarbonInterface $now = null): bool
    {
        if ($this->expiryTime === null) {
            return true;
        }

        return $this->expiryTime->lessThanOrEqualTo($now ?? CarbonImmutable::now());
    }
}
