<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\GooglePlay\Order;
use ReceiptValidator\GooglePlay\OrderLineItem;
use ReceiptValidator\GooglePlay\OrderRefundReason;
use ReceiptValidator\GooglePlay\OrderState;
use ReceiptValidator\GooglePlay\PartialRefundEvent;
use ReceiptValidator\GooglePlay\SalesChannel;
use ReceiptValidator\GooglePlay\SubscriptionOfferPhase;

#[CoversClass(Order::class)]
#[CoversClass(OrderLineItem::class)]
#[CoversClass(PartialRefundEvent::class)]
final class OrderTest extends TestCase
{
    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/fixtures/{$name}.json"), true);
    }

    public function testParsesRefundedSubscriptionOrder(): void
    {
        $data  = $this->fixture('order');
        $order = new Order($data, Environment::SANDBOX);

        self::assertSame('GPA.3333-4444-5555-66666..5', $order->getOrderId());
        self::assertSame('sub-token-123', $order->getPurchaseToken());
        self::assertSame(OrderState::REFUNDED, $order->getState());
        self::assertTrue($order->isRefunded());
        self::assertFalse($order->isPartiallyRefunded());
        self::assertFalse($order->isProcessed());
        self::assertFalse($order->isPending());
        self::assertFalse($order->isCanceled());
        self::assertFalse($order->isRefundPending());
        self::assertSame(SalesChannel::IN_APP, $order->getSalesChannel());
        self::assertSame(Environment::SANDBOX, $order->getEnvironment());
        self::assertSame('2026-09-05T14:32:11Z', $order->getCreateTime()?->toIso8601ZuluString());
        self::assertSame('2026-09-10T09:00:00Z', $order->getLastEventTime()?->toIso8601ZuluString());
        self::assertSame('US', $order->getBuyerCountry());
        self::assertSame('CA', $order->getBuyerState());
        self::assertSame('94105', $order->getBuyerPostcode());
        self::assertSame('10.89', $order->getTotal()?->toDecimalString());
        self::assertSame('0.9', $order->getTax()?->toDecimalString());
        self::assertTrue($order->isTaxInclusive());
        self::assertSame('8.49', $order->getDeveloperRevenueInBuyerCurrency()?->toDecimalString());
        self::assertSame('2026-09-05T14:32:15Z', $order->getProcessedTime()?->toIso8601ZuluString());
        self::assertNull($order->getCancellationTime());
        self::assertSame('2026-09-10T09:00:00Z', $order->getRefundTime()?->toIso8601ZuluString());
        self::assertSame(OrderRefundReason::CHARGEBACK, $order->getRefundReason());
        self::assertTrue($order->isChargeback());
        self::assertSame('10.89', $order->getRefundedTotal()?->toDecimalString());
        self::assertSame('0.9', $order->getRefundedTax()?->toDecimalString());
        self::assertSame('points-10', $order->getPointsOfferId());
        self::assertSame(250, $order->getPointsSpent());
        self::assertSame(['app.example.subscription'], $order->getProductIds());
        self::assertSame($data, $order->getRawData());

        $partials = $order->getPartialRefunds();
        self::assertCount(2, $partials);
        self::assertSame('2026-09-07T00:00:00Z', $partials[0]->getCreateTime()?->toIso8601ZuluString());
        self::assertSame('2026-09-07T00:05:00Z', $partials[0]->getProcessTime()?->toIso8601ZuluString());
        self::assertSame('PROCESSED_SUCCESSFULLY', $partials[0]->getState());
        self::assertTrue($partials[0]->isProcessed());
        self::assertSame('2', $partials[0]->getTotal()?->toDecimalString());
        self::assertNull($partials[0]->getTax());
        self::assertFalse($partials[1]->isProcessed());
        self::assertNull($partials[1]->getProcessTime());
        self::assertNull($partials[1]->getTotal());

        $items = $order->getLineItems();
        self::assertCount(1, $items);
        self::assertSame($items, $order->getTransactions());

        $item = $items[0];
        self::assertSame('Example Premium (annual)', $item->getProductTitle());
        self::assertSame('app.example.subscription', $item->getProductId());
        self::assertSame('GPA.3333-4444-5555-66666..5', $item->getTransactionId());
        self::assertSame(1, $item->getQuantity());
        self::assertSame('9.99', $item->getListingPrice()?->toDecimalString());
        self::assertSame('10.89', $item->getTotal()?->toDecimalString());
        self::assertSame('0.9', $item->getTax()?->toDecimalString());
        self::assertTrue($item->isSubscription());
        self::assertFalse($item->isOneTimePurchase());
        self::assertFalse($item->isPaidApp());
        self::assertSame('annually', $item->getBasePlanId());
        self::assertSame('intro-7day', $item->getOfferId());
        self::assertNull($item->getPurchaseOptionId());
        self::assertSame(SubscriptionOfferPhase::INTRODUCTORY_PRICE, $item->getOfferPhase());
        self::assertSame('2026-09-05T14:32:11Z', $item->getServicePeriodStartTime()?->toIso8601ZuluString());
        self::assertSame('2027-09-05T14:32:11Z', $item->getServicePeriodEndTime()?->toIso8601ZuluString());
        self::assertFalse($item->isPreorder());
        self::assertFalse($item->isRental());
    }

    public function testParsesOneTimeOrderWithMixedLineItems(): void
    {
        $order = new Order($this->fixture('orderOneTime'));

        self::assertTrue($order->isProcessed());
        self::assertSame(SalesChannel::PLAY_STORE, $order->getSalesChannel());
        self::assertSame(Environment::PRODUCTION, $order->getEnvironment());
        self::assertFalse($order->isTaxInclusive());
        self::assertNull($order->getTax());
        self::assertNull($order->getBuyerCountry());
        self::assertNull($order->getRefundTime());
        self::assertSame(OrderRefundReason::UNSPECIFIED, $order->getRefundReason());
        self::assertFalse($order->isChargeback());
        self::assertSame([], $order->getPartialRefunds());
        self::assertNull($order->getPointsOfferId());
        self::assertNull($order->getPointsSpent());

        [$coins, $pro, $app] = $order->getLineItems();

        self::assertTrue($coins->isOneTimePurchase());
        self::assertSame(2, $coins->getQuantity());
        self::assertSame('coins-launch', $coins->getOfferId());
        self::assertSame('default', $coins->getPurchaseOptionId());
        self::assertTrue($coins->isRental());
        self::assertFalse($coins->isPreorder());
        self::assertNull($coins->getBasePlanId());
        self::assertSame(SubscriptionOfferPhase::UNSPECIFIED, $coins->getOfferPhase());
        self::assertNull($coins->getServicePeriodStartTime());

        // Deprecated string offerPhase is honoured when offerPhaseDetails is absent.
        self::assertTrue($pro->isSubscription());
        self::assertSame(SubscriptionOfferPhase::FREE_TRIAL, $pro->getOfferPhase());
        self::assertNull($pro->getOfferId());
        self::assertNull($pro->getTotal());

        self::assertTrue($app->isPaidApp());
        self::assertFalse($app->isSubscription());
        self::assertSame(1, $app->getQuantity());
    }

    public function testEmptyPayloadIsSafe(): void
    {
        $order = new Order([]);

        self::assertNull($order->getOrderId());
        self::assertSame(OrderState::UNSPECIFIED, $order->getState());
        self::assertSame(SalesChannel::UNSPECIFIED, $order->getSalesChannel());
        self::assertNull($order->getTotal());
        self::assertSame([], $order->getLineItems());
        self::assertSame([], $order->getProductIds());
    }
}
