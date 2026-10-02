<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * One product in an order: a one-time product, a subscription period or a paid app.
 *
 * The transaction ID exposed via the base class is the parent order ID.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders#LineItem
 */
final readonly class OrderLineItem extends AbstractTransaction
{
    /** The product's title as listed on Play. */
    public ?string $productTitle;

    /** The listed price before tax and discounts. */
    public ?Money $listingPrice;

    /** The amount paid for this item, including tax when the order is tax inclusive. */
    public ?Money $total;

    /** Tax on this item. */
    public ?Money $tax;

    /** True for a one-time product. */
    public bool $isOneTimePurchase;

    /** True for a subscription period. */
    public bool $isSubscription;

    /** True for a paid app. */
    public bool $isPaidApp;

    /** The offer applied, for one-time products and subscriptions. */
    public ?string $offerId;

    /** The purchase option bought, for one-time products. */
    public ?string $purchaseOptionId;

    /** True when a one-time product was pre-ordered. */
    public bool $isPreorder;

    /** True when a one-time product is a rental. */
    public bool $isRental;

    /** The base plan, for subscriptions. */
    public ?string $basePlanId;

    /** Which phase of the offer this period was charged under, for subscriptions. */
    public SubscriptionOfferPhase $offerPhase;

    /** Start of the service period this order paid for, for subscriptions. */
    public ?CarbonImmutable $servicePeriodStartTime;

    /** End of the service period this order paid for, for subscriptions. */
    public ?CarbonImmutable $servicePeriodEndTime;

    /**
     * @param array<string, mixed> $data    A single entry from `lineItems`.
     * @param string|null          $orderId The parent order ID.
     */
    public function __construct(array $data = [], ?string $orderId = null)
    {
        $oneTime      = is_array($data['oneTimePurchaseDetails'] ?? null) ? $data['oneTimePurchaseDetails'] : null;
        $subscription = is_array($data['subscriptionDetails'] ?? null) ? $data['subscriptionDetails'] : null;

        parent::__construct(
            rawData: $data,
            quantity: $oneTime !== null ? ($this->toInt($oneTime, 'quantity') ?? 1) : 1,
            productId: $this->toString($data, 'productId'),
            transactionId: $orderId,
        );

        $this->productTitle = $this->toString($data, 'productTitle');
        $this->listingPrice = Money::fromArray($data['listingPrice'] ?? null);
        $this->total        = Money::fromArray($data['total'] ?? null);
        $this->tax          = Money::fromArray($data['tax'] ?? null);

        $this->isOneTimePurchase = $oneTime !== null;
        $this->isSubscription    = $subscription !== null;
        $this->isPaidApp         = array_key_exists('paidAppDetails', $data) && $data['paidAppDetails'] !== null;

        $this->purchaseOptionId = $oneTime !== null ? $this->toString($oneTime, 'purchaseOptionId') : null;
        $this->isPreorder       = $oneTime !== null && array_key_exists('preorderDetails', $oneTime) && $oneTime['preorderDetails'] !== null;
        $this->isRental         = $oneTime !== null && array_key_exists('rentalDetails', $oneTime) && $oneTime['rentalDetails'] !== null;

        $this->offerId    = $this->toString($oneTime ?? [], 'offerId') ?? $this->toString($subscription ?? [], 'offerId');
        $this->basePlanId = $subscription !== null ? $this->toString($subscription, 'basePlanId') : null;

        $phaseDetails     = is_array($subscription['offerPhaseDetails'] ?? null) ? $subscription['offerPhaseDetails'] : [];
        $this->offerPhase = match (true) {
            isset($phaseDetails['freeTrialDetails'])         => SubscriptionOfferPhase::FREE_TRIAL,
            isset($phaseDetails['introductoryPriceDetails']) => SubscriptionOfferPhase::INTRODUCTORY_PRICE,
            isset($phaseDetails['baseDetails'])              => SubscriptionOfferPhase::BASE_PRICE,
            isset($phaseDetails['prorationPeriodDetails'])   => SubscriptionOfferPhase::PRORATION_PERIOD,
            default                                          => match ($this->toString($subscription ?? [], 'offerPhase')) {
                'FREE_TRIAL'   => SubscriptionOfferPhase::FREE_TRIAL,
                'INTRODUCTORY' => SubscriptionOfferPhase::INTRODUCTORY_PRICE,
                'BASE'         => SubscriptionOfferPhase::BASE_PRICE,
                default        => SubscriptionOfferPhase::UNSPECIFIED,
            },
        };

        $this->servicePeriodStartTime = $subscription !== null ? $this->toDateFromRfc3339($subscription, 'servicePeriodStartTime') : null;
        $this->servicePeriodEndTime   = $subscription !== null ? $this->toDateFromRfc3339($subscription, 'servicePeriodEndTime') : null;
    }

    public function getProductTitle(): ?string
    {
        return $this->productTitle;
    }

    public function getListingPrice(): ?Money
    {
        return $this->listingPrice;
    }

    public function getTotal(): ?Money
    {
        return $this->total;
    }

    public function getTax(): ?Money
    {
        return $this->tax;
    }

    public function isOneTimePurchase(): bool
    {
        return $this->isOneTimePurchase;
    }

    public function isSubscription(): bool
    {
        return $this->isSubscription;
    }

    public function isPaidApp(): bool
    {
        return $this->isPaidApp;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    public function getPurchaseOptionId(): ?string
    {
        return $this->purchaseOptionId;
    }

    public function isPreorder(): bool
    {
        return $this->isPreorder;
    }

    public function isRental(): bool
    {
        return $this->isRental;
    }

    public function getBasePlanId(): ?string
    {
        return $this->basePlanId;
    }

    public function getOfferPhase(): SubscriptionOfferPhase
    {
        return $this->offerPhase;
    }

    public function getServicePeriodStartTime(): ?CarbonInterface
    {
        return $this->servicePeriodStartTime;
    }

    public function getServicePeriodEndTime(): ?CarbonInterface
    {
        return $this->servicePeriodEndTime;
    }
}
