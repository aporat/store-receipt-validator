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

    /** The product this item will be replaced with at the next renewal, if a deferred replacement is pending. */
    public ?string $deferredItemReplacementProductId;

    /**
     * The recurring price of an auto-renewing plan (`{currencyCode, units, nanos}`), if present.
     *
     * @var array<string, mixed>|null
     */
    public ?array $recurringPrice;

    /**
     * Pending or applied price change details for an auto-renewing plan, if present.
     *
     * @var array<string, mixed>|null
     */
    public ?array $priceChangeDetails;

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

        $this->isAutoRenewingPlan = $autoRenewing !== null;
        $this->autoRenewEnabled   = $autoRenewing !== null && $this->toBool($autoRenewing, 'autoRenewEnabled');
        $this->recurringPrice     = is_array($autoRenewing['recurringPrice'] ?? null) ? $autoRenewing['recurringPrice'] : null;
        $this->priceChangeDetails = is_array($autoRenewing['priceChangeDetails'] ?? null) ? $autoRenewing['priceChangeDetails'] : null;

        $this->isPrepaidPlan               = $prepaid !== null;
        $this->prepaidAllowExtendAfterTime = $prepaid !== null ? $this->toDateFromRfc3339($prepaid, 'allowExtendAfterTime') : null;

        $this->basePlanId = $this->toString($offer, 'basePlanId');
        $this->offerId    = $this->toString($offer, 'offerId');
        $this->offerTags  = array_values(array_map('strval', is_array($offer['offerTags'] ?? null) ? $offer['offerTags'] : []));

        $this->deferredItemReplacementProductId = $this->toString($deferred, 'productId');
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

    public function getDeferredItemReplacementProductId(): ?string
    {
        return $this->deferredItemReplacementProductId;
    }

    /** @return array<string, mixed>|null */
    public function getRecurringPrice(): ?array
    {
        return $this->recurringPrice;
    }

    /** @return array<string, mixed>|null */
    public function getPriceChangeDetails(): ?array
    {
        return $this->priceChangeDetails;
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
