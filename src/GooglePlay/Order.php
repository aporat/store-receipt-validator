<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use ReceiptValidator\AbstractResponse;
use ReceiptValidator\Environment;

/**
 * An order as returned by `orders.get` and `orders.batchget`.
 *
 * Orders are the financial record behind a purchase token: what the user was charged,
 * tax, buyer country, the service period a subscription payment covered, and whether
 * and why it was refunded. Line items are exposed as the response's transactions.
 *
 * @see https://developers.google.com/android-publisher/api-ref/rest/v3/orders#Order
 *
 * @extends AbstractResponse<OrderLineItem>
 */
final class Order extends AbstractResponse
{
    public readonly ?string $orderId;

    /** The purchase token this order belongs to. */
    public readonly ?string $purchaseToken;

    public readonly OrderState $state;

    public readonly SalesChannel $salesChannel;

    /** When the order was created. */
    public readonly ?CarbonImmutable $createTime;

    /** When the order last changed. */
    public readonly ?CarbonImmutable $lastEventTime;

    /** ISO 3166-1 alpha-2 country of the buyer. */
    public readonly ?string $buyerCountry;

    /** Top-level region of the buyer, for tax purposes. */
    public readonly ?string $buyerState;

    /** Postcode of the buyer, for tax purposes. */
    public readonly ?string $buyerPostcode;

    /** The amount charged. Includes tax when {@see $taxInclusive} is true. */
    public readonly ?Money $total;

    /** The tax charged. */
    public readonly ?Money $tax;

    /** Whether {@see $total} includes {@see $tax}. */
    public readonly bool $taxInclusive;

    /** The developer's share, in the buyer's currency. */
    public readonly ?Money $developerRevenueInBuyerCurrency;

    /** When payment was processed. */
    public readonly ?CarbonImmutable $processedTime;

    /** When the order was canceled, if it was. */
    public readonly ?CarbonImmutable $cancellationTime;

    /** When the order was fully refunded, if it was. */
    public readonly ?CarbonImmutable $refundTime;

    /** Why the order was fully refunded. UNSPECIFIED when it was not. */
    public readonly OrderRefundReason $refundReason;

    /** The amount refunded by a full refund. */
    public readonly ?Money $refundedTotal;

    /** The tax refunded by a full refund. */
    public readonly ?Money $refundedTax;

    /**
     * Partial refunds, oldest first.
     *
     * @var array<int, PartialRefundEvent>
     */
    public readonly array $partialRefunds;

    /** Play Points offer applied, if any. */
    public readonly ?string $pointsOfferId;

    /** Play Points spent on this order, if any. */
    public readonly ?int $pointsSpent;

    /**
     * @param array<string, mixed> $data The decoded `Order` JSON.
     */
    public function __construct(array $data = [], Environment $environment = Environment::PRODUCTION)
    {
        parent::__construct($data, $environment);

        $this->orderId       = $this->toString($data, 'orderId');
        $this->purchaseToken = $this->toString($data, 'purchaseToken');
        $this->state         = OrderState::fromString($this->toString($data, 'state'));
        $this->salesChannel  = SalesChannel::fromString($this->toString($data, 'salesChannel'));
        $this->createTime    = $this->toDateFromRfc3339($data, 'createTime');
        $this->lastEventTime = $this->toDateFromRfc3339($data, 'lastEventTime');

        $address             = is_array($data['buyerAddress'] ?? null) ? $data['buyerAddress'] : [];
        $this->buyerCountry  = $this->toString($address, 'buyerCountry');
        $this->buyerState    = $this->toString($address, 'buyerState');
        $this->buyerPostcode = $this->toString($address, 'buyerPostcode');

        $this->total = Money::fromArray($data['total'] ?? null);
        $this->tax   = Money::fromArray($data['tax'] ?? null);

        $details            = is_array($data['orderDetails'] ?? null) ? $data['orderDetails'] : [];
        $this->taxInclusive = $this->toBool($details, 'taxInclusive');

        $this->developerRevenueInBuyerCurrency = Money::fromArray($data['developerRevenueInBuyerCurrency'] ?? null);

        $history      = is_array($data['orderHistory'] ?? null) ? $data['orderHistory'] : [];
        $processed    = is_array($history['processedEvent'] ?? null) ? $history['processedEvent'] : [];
        $cancellation = is_array($history['cancellationEvent'] ?? null) ? $history['cancellationEvent'] : [];
        $refund       = is_array($history['refundEvent'] ?? null) ? $history['refundEvent'] : [];
        $refundDetail = is_array($refund['refundDetails'] ?? null) ? $refund['refundDetails'] : [];

        $this->processedTime    = $this->toDateFromRfc3339($processed, 'eventTime');
        $this->cancellationTime = $this->toDateFromRfc3339($cancellation, 'eventTime');
        $this->refundTime       = $this->toDateFromRfc3339($refund, 'eventTime');
        $this->refundReason     = OrderRefundReason::fromString($this->toString($refund, 'refundReason'));
        $this->refundedTotal    = Money::fromArray($refundDetail['total'] ?? null);
        $this->refundedTax      = Money::fromArray($refundDetail['tax'] ?? null);

        $partials = [];
        foreach (is_array($history['partialRefundEvents'] ?? null) ? $history['partialRefundEvents'] : [] as $event) {
            if (is_array($event)) {
                $partials[] = new PartialRefundEvent($event);
            }
        }
        $this->partialRefunds = $partials;

        $points              = is_array($data['pointsDetails'] ?? null) ? $data['pointsDetails'] : [];
        $this->pointsOfferId = $this->toString($points, 'pointsOfferId');
        $this->pointsSpent   = $this->toInt($points, 'pointsSpent');

        $items = [];
        foreach (is_array($data['lineItems'] ?? null) ? $data['lineItems'] : [] as $item) {
            if (is_array($item)) {
                $items[] = new OrderLineItem($item, $this->orderId);
            }
        }
        $this->setTransactions($items);
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    public function getState(): OrderState
    {
        return $this->state;
    }

    public function isPending(): bool
    {
        return $this->state === OrderState::PENDING;
    }

    public function isProcessed(): bool
    {
        return $this->state === OrderState::PROCESSED;
    }

    public function isCanceled(): bool
    {
        return $this->state === OrderState::CANCELED;
    }

    /** True for a full refund; see {@see isPartiallyRefunded()} for partial ones. */
    public function isRefunded(): bool
    {
        return $this->state === OrderState::REFUNDED;
    }

    public function isPartiallyRefunded(): bool
    {
        return $this->state === OrderState::PARTIALLY_REFUNDED;
    }

    public function isRefundPending(): bool
    {
        return $this->state === OrderState::PENDING_REFUND;
    }

    public function getSalesChannel(): SalesChannel
    {
        return $this->salesChannel;
    }

    public function getCreateTime(): ?CarbonInterface
    {
        return $this->createTime;
    }

    public function getLastEventTime(): ?CarbonInterface
    {
        return $this->lastEventTime;
    }

    public function getBuyerCountry(): ?string
    {
        return $this->buyerCountry;
    }

    public function getBuyerState(): ?string
    {
        return $this->buyerState;
    }

    public function getBuyerPostcode(): ?string
    {
        return $this->buyerPostcode;
    }

    public function getTotal(): ?Money
    {
        return $this->total;
    }

    public function getTax(): ?Money
    {
        return $this->tax;
    }

    public function isTaxInclusive(): bool
    {
        return $this->taxInclusive;
    }

    public function getDeveloperRevenueInBuyerCurrency(): ?Money
    {
        return $this->developerRevenueInBuyerCurrency;
    }

    public function getProcessedTime(): ?CarbonInterface
    {
        return $this->processedTime;
    }

    public function getCancellationTime(): ?CarbonInterface
    {
        return $this->cancellationTime;
    }

    public function getRefundTime(): ?CarbonInterface
    {
        return $this->refundTime;
    }

    public function getRefundReason(): OrderRefundReason
    {
        return $this->refundReason;
    }

    public function isChargeback(): bool
    {
        return $this->refundReason === OrderRefundReason::CHARGEBACK;
    }

    public function getRefundedTotal(): ?Money
    {
        return $this->refundedTotal;
    }

    public function getRefundedTax(): ?Money
    {
        return $this->refundedTax;
    }

    /** @return array<int, PartialRefundEvent> */
    public function getPartialRefunds(): array
    {
        return $this->partialRefunds;
    }

    public function getPointsOfferId(): ?string
    {
        return $this->pointsOfferId;
    }

    public function getPointsSpent(): ?int
    {
        return $this->pointsSpent;
    }

    /**
     * The line items of this order. Alias of {@see getTransactions()}.
     *
     * @return array<OrderLineItem>
     */
    public function getLineItems(): array
    {
        return $this->getTransactions();
    }

    /**
     * Product IDs of all line items.
     *
     * @return array<int, string>
     */
    public function getProductIds(): array
    {
        $ids = [];
        foreach ($this->getTransactions() as $item) {
            if ($item->getProductId() !== null) {
                $ids[] = $item->getProductId();
            }
        }

        return $ids;
    }
}
