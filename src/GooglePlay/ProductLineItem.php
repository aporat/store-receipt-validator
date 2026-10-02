<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractTransaction;

/**
 * One item of a `purchases.productsv2` purchase: a single one-time product and its offer.
 *
 * The transaction ID exposed via the base class is the order ID of the parent purchase.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.productsv2#ProductLineItem
 */
final readonly class ProductLineItem extends AbstractTransaction
{
    /** The offer the user redeemed, if any. */
    public ?string $offerId;

    /** The purchase option (one-time product variant) that was bought. */
    public ?string $purchaseOptionId;

    /** The per-transaction offer token used for this line item. */
    public ?string $offerToken;

    /**
     * Tags attached to the redeemed offer.
     *
     * @var array<int, string>
     */
    public array $offerTags;

    /** The quantity that has not yet been refunded. */
    public ?int $refundableQuantity;

    /** The consumption state, or null if Google sent an unknown value. */
    public ?ConsumptionState $consumptionState;

    /** True when this item is a rental. */
    public bool $isRental;

    /** True when this item was pre-ordered. */
    public bool $isPreorder;

    /** For pre-orders, when the item is released. */
    public ?CarbonImmutable $preorderReleaseTime;

    /**
     * @param array<string, mixed> $data    A single entry from `productLineItem`.
     * @param string|null          $orderId The order ID of the parent purchase.
     */
    public function __construct(array $data = [], ?string $orderId = null)
    {
        $offer = is_array($data['productOfferDetails'] ?? null) ? $data['productOfferDetails'] : [];

        parent::__construct(
            rawData: $data,
            quantity: $this->toInt($offer, 'quantity') ?? 1,
            productId: $this->toString($data, 'productId'),
            transactionId: $orderId,
        );

        $this->offerId            = $this->toString($offer, 'offerId');
        $this->purchaseOptionId   = $this->toString($offer, 'purchaseOptionId');
        $this->offerToken         = $this->toString($offer, 'offerToken');
        $this->offerTags          = array_values(array_map('strval', is_array($offer['offerTags'] ?? null) ? $offer['offerTags'] : []));
        $this->refundableQuantity = $this->toInt($offer, 'refundableQuantity');
        $this->consumptionState   = ConsumptionState::fromV2String($this->toString($offer, 'consumptionState'));

        $this->isRental = array_key_exists('rentOfferDetails', $offer) && $offer['rentOfferDetails'] !== null;

        $preorder                  = is_array($offer['preorderOfferDetails'] ?? null) ? $offer['preorderOfferDetails'] : null;
        $this->isPreorder          = $preorder !== null;
        $this->preorderReleaseTime = $preorder !== null ? $this->toDateFromRfc3339($preorder, 'preorderReleaseTime') : null;
    }

    public function getOfferId(): ?string
    {
        return $this->offerId;
    }

    public function getPurchaseOptionId(): ?string
    {
        return $this->purchaseOptionId;
    }

    public function getOfferToken(): ?string
    {
        return $this->offerToken;
    }

    /** @return array<int, string> */
    public function getOfferTags(): array
    {
        return $this->offerTags;
    }

    public function getRefundableQuantity(): ?int
    {
        return $this->refundableQuantity;
    }

    public function getConsumptionState(): ?ConsumptionState
    {
        return $this->consumptionState;
    }

    public function isConsumed(): bool
    {
        return $this->consumptionState === ConsumptionState::CONSUMED;
    }

    public function isRental(): bool
    {
        return $this->isRental;
    }

    public function isPreorder(): bool
    {
        return $this->isPreorder;
    }

    public function getPreorderReleaseTime(): ?CarbonInterface
    {
        return $this->preorderReleaseTime;
    }
}
