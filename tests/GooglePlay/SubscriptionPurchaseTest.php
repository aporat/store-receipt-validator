<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\GooglePlay\AcknowledgementState;
use ReceiptValidator\GooglePlay\SubscriptionLineItem;
use ReceiptValidator\GooglePlay\SubscriptionPurchase;
use ReceiptValidator\GooglePlay\SubscriptionState;

#[CoversClass(SubscriptionPurchase::class)]
#[CoversClass(SubscriptionLineItem::class)]
final class SubscriptionPurchaseTest extends TestCase
{
    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/fixtures/{$name}.json"), true);
    }

    public function testParsesActiveSubscription(): void
    {
        $purchase = new SubscriptionPurchase($this->fixture('subscriptionPurchaseV2'));

        self::assertSame('androidpublisher#subscriptionPurchaseV2', $purchase->getKind());
        self::assertSame('US', $purchase->getRegionCode());
        self::assertSame('GPA.3333-4444-5555-66666..5', $purchase->getLatestOrderId());
        self::assertSame('2025-10-05T14:32:11.512000Z', $purchase->getStartTime()?->toIso8601ZuluString('microsecond'));
        self::assertSame(SubscriptionState::ACTIVE, $purchase->getSubscriptionState());
        self::assertSame(AcknowledgementState::ACKNOWLEDGED, $purchase->getAcknowledgementState());
        self::assertTrue($purchase->isAcknowledged());
        self::assertFalse($purchase->isTestPurchase());
        self::assertSame(Environment::PRODUCTION, $purchase->getEnvironment());
        self::assertNull($purchase->getLinkedPurchaseToken());
        self::assertNull($purchase->getExternalAccountId());
        self::assertSame('5E8A3B2C-1D4F-4E6A-9B7C-8D9E0F1A2B3C', $purchase->getObfuscatedExternalAccountId());
        self::assertSame('profile-1', $purchase->getObfuscatedExternalProfileId());
        self::assertNull($purchase->getAutoResumeTime());
        self::assertNull($purchase->getCanceledStateContext());
        self::assertNull($purchase->getSubscribeWithGoogleInfo());
        self::assertTrue($purchase->isAutoRenewing());
        self::assertSame(['app.example.subscription'], $purchase->getProductIds());
        self::assertSame($purchase->getTransactions(), $purchase->getLineItems());
        self::assertCount(1, $purchase->getLineItems());

        $item = $purchase->getLatestLineItem();
        self::assertInstanceOf(SubscriptionLineItem::class, $item);
        self::assertSame('app.example.subscription', $item->getProductId());
        self::assertSame('GPA.3333-4444-5555-66666..5', $item->getTransactionId());
        self::assertSame('GPA.3333-4444-5555-66666..5', $item->getLatestSuccessfulOrderId());
        self::assertSame(1, $item->getQuantity());
        self::assertSame('2026-10-05T14:32:11.512000Z', $item->getExpiryTime()?->toIso8601ZuluString('microsecond'));
        self::assertSame($item->getExpiryTime(), $purchase->getExpiryTime());
        self::assertTrue($item->isAutoRenewingPlan());
        self::assertTrue($item->isAutoRenewEnabled());
        self::assertFalse($item->isPrepaidPlan());
        self::assertNull($item->getPrepaidAllowExtendAfterTime());
        self::assertSame('annually', $item->getBasePlanId());
        self::assertSame('intro-7day', $item->getOfferId());
        self::assertSame(['intro', 'campaign-fall'], $item->getOfferTags());
        self::assertNull($item->getDeferredItemReplacementProductId());
        self::assertSame(['currencyCode' => 'USD', 'units' => '9', 'nanos' => 990000000], $item->getRecurringPrice());
        self::assertNull($item->getPriceChangeDetails());
        self::assertSame($this->fixture('subscriptionPurchaseV2')['lineItems'][0], $item->getRawData());

        self::assertFalse($item->isExpired(CarbonImmutable::parse('2026-10-05T14:32:11Z')));
        self::assertTrue($item->isExpired(CarbonImmutable::parse('2026-10-05T14:32:12Z')));

        self::assertTrue($purchase->isEntitled(CarbonImmutable::parse('2026-09-05T00:00:00Z')));
        self::assertFalse($purchase->isEntitled(CarbonImmutable::parse('2026-11-01T00:00:00Z')));
    }

    public function testParsesCanceledTestPurchaseWithMultipleLineItems(): void
    {
        $data     = $this->fixture('subscriptionPurchaseV2Canceled');
        $purchase = new SubscriptionPurchase($data);

        self::assertSame(SubscriptionState::CANCELED, $purchase->getSubscriptionState());
        self::assertSame(AcknowledgementState::PENDING, $purchase->getAcknowledgementState());
        self::assertFalse($purchase->isAcknowledged());
        self::assertTrue($purchase->isTestPurchase());
        self::assertSame(Environment::SANDBOX, $purchase->getEnvironment());
        self::assertSame('older-token', $purchase->getLinkedPurchaseToken());
        self::assertSame($data['canceledStateContext'], $purchase->getCanceledStateContext());
        self::assertSame($data['subscribeWithGoogleInfo'], $purchase->getSubscribeWithGoogleInfo());
        self::assertSame($data, $purchase->getRawData());
        self::assertFalse($purchase->isAutoRenewing());
        self::assertSame(['app.example.subscription', 'app.example.addon'], $purchase->getProductIds());

        // The add-on expires later, so it is the "latest" item and drives the expiry.
        $latest = $purchase->getLatestLineItem();
        self::assertNotNull($latest);
        self::assertSame('app.example.addon', $latest->getProductId());
        self::assertSame('2027-01-01T00:00:00Z', $purchase->getExpiryTime()?->toIso8601ZuluString());
        self::assertTrue($latest->isPrepaidPlan());
        self::assertFalse($latest->isAutoRenewingPlan());
        self::assertFalse($latest->isAutoRenewEnabled());
        self::assertSame('2026-12-25T00:00:00Z', $latest->getPrepaidAllowExtendAfterTime()?->toIso8601ZuluString());
        self::assertNull($latest->getBasePlanId());
        self::assertSame([], $latest->getOfferTags());

        $base = $purchase->getLineItems()[0];
        self::assertTrue($base->isAutoRenewingPlan());
        self::assertFalse($base->isAutoRenewEnabled());
        self::assertSame('monthly', $base->getBasePlanId());
        self::assertNull($base->getOfferId());
        self::assertSame('app.example.subscription.plus', $base->getDeferredItemReplacementProductId());
        self::assertNull($base->getRecurringPrice());
        self::assertSame('PRICE_INCREASE', $base->getPriceChangeDetails()['priceChangeMode'] ?? null);

        // Canceled keeps entitlement until expiry.
        self::assertTrue($purchase->isEntitled(CarbonImmutable::parse('2026-12-31T00:00:00Z')));
        self::assertFalse($purchase->isEntitled(CarbonImmutable::parse('2027-01-01T00:00:00Z')));
    }

    public function testEmptyPayloadIsSafe(): void
    {
        $purchase = new SubscriptionPurchase([]);

        self::assertSame(SubscriptionState::UNSPECIFIED, $purchase->getSubscriptionState());
        self::assertSame(AcknowledgementState::UNSPECIFIED, $purchase->getAcknowledgementState());
        self::assertSame(Environment::PRODUCTION, $purchase->getEnvironment());
        self::assertNull($purchase->getStartTime());
        self::assertNull($purchase->getLatestLineItem());
        self::assertNull($purchase->getExpiryTime());
        self::assertSame([], $purchase->getProductIds());
        self::assertFalse($purchase->isAutoRenewing());
        self::assertFalse($purchase->isEntitled());
    }

    public function testLineItemsWithoutExpiryFallBackToFirst(): void
    {
        $purchase = new SubscriptionPurchase([
            'subscriptionState' => 'SUBSCRIPTION_STATE_ACTIVE',
            'lineItems'         => [
                ['productId' => 'a'],
                ['productId' => 'b'],
                'not-an-item',
            ],
        ]);

        self::assertCount(2, $purchase->getLineItems());
        self::assertSame('a', $purchase->getLatestLineItem()?->getProductId());
        self::assertNull($purchase->getExpiryTime());
        // No expiry to contradict the ACTIVE state.
        self::assertTrue($purchase->isEntitled());
        self::assertTrue($purchase->getLineItems()[0]->isExpired());
    }

    public function testMixedExpiryLineItemsPickTheLatestDatedOne(): void
    {
        $purchase = new SubscriptionPurchase([
            'lineItems' => [
                ['productId' => 'undated'],
                ['productId' => 'early', 'expiryTime' => '2026-01-01T00:00:00Z'],
                ['productId' => 'late', 'expiryTime' => '2026-06-01T00:00:00Z'],
                ['productId' => 'middle', 'expiryTime' => '2026-03-01T00:00:00Z'],
            ],
        ]);

        self::assertSame('late', $purchase->getLatestLineItem()?->getProductId());
    }

    public function testPausedStateExposesAutoResumeTime(): void
    {
        $purchase = new SubscriptionPurchase([
            'subscriptionState'  => 'SUBSCRIPTION_STATE_PAUSED',
            'pausedStateContext' => ['autoResumeTime' => '2026-10-01T00:00:00Z'],
            'testPurchase'       => null,
        ]);

        self::assertSame('2026-10-01T00:00:00Z', $purchase->getAutoResumeTime()?->toIso8601ZuluString());
        self::assertFalse($purchase->isTestPurchase());
        self::assertFalse($purchase->isEntitled());
    }

    public function testUnknownStateStringsFallBack(): void
    {
        $purchase = new SubscriptionPurchase([
            'subscriptionState'    => 'SUBSCRIPTION_STATE_FROM_THE_FUTURE',
            'acknowledgementState' => 'WHO_KNOWS',
        ]);

        self::assertSame(SubscriptionState::UNSPECIFIED, $purchase->getSubscriptionState());
        self::assertSame(AcknowledgementState::UNSPECIFIED, $purchase->getAcknowledgementState());
    }

    public function testInvalidTimestampsBecomeNull(): void
    {
        $item = new SubscriptionLineItem(['expiryTime' => 'not a date']);

        self::assertNull($item->getExpiryTime());
    }
}
