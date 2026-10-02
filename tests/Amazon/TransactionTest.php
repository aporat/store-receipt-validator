<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\CancelReason;
use ReceiptValidator\Amazon\FulfillmentResult;
use ReceiptValidator\Amazon\ProductType;
use ReceiptValidator\Amazon\Promotion;
use ReceiptValidator\Amazon\PromotionStatus;
use ReceiptValidator\Amazon\PromotionType;
use ReceiptValidator\Amazon\Transaction;

/**
 * @group amazon
 */
#[CoversClass(Transaction::class)]
#[CoversClass(Promotion::class)]
final class TransactionTest extends TestCase
{
    private const int BASE_MS = 1_609_459_200_000; // 2021-01-01T00:00:00Z

    public function testFullyPopulatedSubscription(): void
    {
        $base = self::BASE_MS;
        $raw  = [
            'autoRenewing'        => true,
            'baseReceipts'        => ['base-receipt-1', 'base-receipt-2', ['not' => 'scalar']],
            'betaProduct'         => true,
            'cancelDate'          => null,
            'cancelReason'        => null,
            'countryCode'         => 'DE',
            'deferredDate'        => $base + 10_000,
            'deferredSku'         => 'sub1-yearly',
            'freeTrialEndDate'    => $base + 7_000,
            'fulfillmentDate'     => $base + 1_000,
            'fulfillmentResult'   => 'FULFILLED',
            'gracePeriodEndDate'  => $base + 9_000,
            'parentProductId'     => 'parent-sku',
            'productId'           => 'com.amazon.sample',
            'productType'         => 'SUBSCRIPTION',
            'promotions'          => [
                ['promotionType' => 'Retention Offer', 'promotionStatus' => 'InProgress'],
                ['promotionType' => 'Something New', 'promotionStatus' => 'Unknown'],
                'not-an-object',
            ],
            'purchaseDate'        => $base,
            'purchaseMetadataMap' => ['QuickSubscribe' => 'true', 'nested' => ['ignored']],
            'quantity'            => 1,
            'receiptId'           => 'txn123',
            'renewalDate'         => $base + 8_000,
            'term'                => '1 Month',
            'termSku'             => 'sub1-monthly',
            'testTransaction'     => false,
        ];

        $t = new Transaction($raw);

        self::assertSame($raw, $t->getRawData());
        self::assertSame(1, $t->getQuantity());
        self::assertSame('com.amazon.sample', $t->getProductId());
        self::assertSame('txn123', $t->getTransactionId());
        self::assertSame(ProductType::SUBSCRIPTION, $t->getProductType());
        self::assertTrue($t->isSubscription());
        self::assertFalse($t->isConsumable());
        self::assertFalse($t->isEntitlement());
        self::assertSame('parent-sku', $t->getParentProductId());

        self::assertMs($base, $t->getPurchaseDate());
        self::assertNull($t->getCancellationDate());
        self::assertNull($t->getCancelReason());
        self::assertFalse($t->isCanceled());
        self::assertMs($base + 8_000, $t->getRenewalDate());
        self::assertMs($base + 9_000, $t->getGracePeriodEndDate());
        self::assertMs($base + 7_000, $t->getFreeTrialEndDate());
        self::assertMs($base + 10_000, $t->getDeferredDate());
        self::assertSame('sub1-yearly', $t->getDeferredSku());

        self::assertTrue($t->isAutoRenewing());
        self::assertSame('1 Month', $t->getTerm());
        self::assertSame('sub1-monthly', $t->getTermSku());

        self::assertMs($base + 1_000, $t->getFulfillmentDate());
        self::assertSame(FulfillmentResult::FULFILLED, $t->getFulfillmentResult());

        $promotions = $t->getPromotions();
        self::assertCount(2, $promotions);
        self::assertContainsOnlyInstancesOf(Promotion::class, $promotions);
        self::assertSame(PromotionType::RETENTION_OFFER, $promotions[0]->getType());
        self::assertSame(PromotionStatus::IN_PROGRESS, $promotions[0]->getStatus());
        self::assertTrue($promotions[0]->isActive());
        self::assertNull($promotions[1]->getType());
        self::assertNull($promotions[1]->getStatus());
        self::assertFalse($promotions[1]->isActive());
        self::assertSame(['promotionType' => 'Something New', 'promotionStatus' => 'Unknown'], $promotions[1]->getRawData());
        self::assertSame($promotions[0], $t->getActivePromotion());

        self::assertSame(['base-receipt-1', 'base-receipt-2'], $t->getBaseReceipts());
        self::assertTrue($t->isAddOnSubscription());
        self::assertSame(['QuickSubscribe' => 'true'], $t->getPurchaseMetadataMap());
        self::assertTrue($t->isQuickSubscribe());

        self::assertSame('DE', $t->getCountryCode());
        self::assertTrue($t->isBetaProduct());
        self::assertFalse($t->isTestTransaction());
    }

    public function testEmptyDataUsesDefaults(): void
    {
        $t = new Transaction([]);

        self::assertSame(1, $t->getQuantity());
        self::assertNull($t->getProductId());
        self::assertNull($t->getTransactionId());
        self::assertNull($t->getProductType());
        self::assertFalse($t->isSubscription());
        self::assertFalse($t->isConsumable());
        self::assertFalse($t->isEntitlement());
        self::assertNull($t->getParentProductId());
        self::assertNull($t->getPurchaseDate());
        self::assertNull($t->getCancellationDate());
        self::assertNull($t->getCancelReason());
        self::assertNull($t->getRenewalDate());
        self::assertNull($t->getGracePeriodEndDate());
        self::assertNull($t->getFreeTrialEndDate());
        self::assertNull($t->getDeferredDate());
        self::assertNull($t->getDeferredSku());
        self::assertFalse($t->isAutoRenewing());
        self::assertNull($t->getTerm());
        self::assertNull($t->getTermSku());
        self::assertNull($t->getFulfillmentDate());
        self::assertNull($t->getFulfillmentResult());
        self::assertSame([], $t->getPromotions());
        self::assertNull($t->getActivePromotion());
        self::assertNull($t->getBaseReceipts());
        self::assertFalse($t->isAddOnSubscription());
        self::assertNull($t->getPurchaseMetadataMap());
        self::assertFalse($t->isQuickSubscribe());
        self::assertNull($t->getCountryCode());
        self::assertFalse($t->isBetaProduct());
        self::assertFalse($t->isTestTransaction());
        self::assertNull($t->getExpiresAt());
        self::assertFalse($t->isInFreeTrial());
        self::assertFalse($t->isInGracePeriod());
    }

    public function testLegacyCapitalisedKeysAreAccepted(): void
    {
        $t = new Transaction([
            'AutoRenewing'       => true,
            'GracePeriodEndDate' => self::BASE_MS,
        ]);

        self::assertTrue($t->isAutoRenewing());
        self::assertMs(self::BASE_MS, $t->getGracePeriodEndDate());
    }

    public function testCamelCaseKeysWinOverLegacyKeys(): void
    {
        $t = new Transaction([
            'autoRenewing'       => false,
            'AutoRenewing'       => true,
            'gracePeriodEndDate' => null,
            'GracePeriodEndDate' => self::BASE_MS,
        ]);

        self::assertFalse($t->isAutoRenewing());
        self::assertNull($t->getGracePeriodEndDate());
    }

    public function testUnknownEnumValuesBecomeNull(): void
    {
        $t = new Transaction([
            'productType'       => 'SOMETHING_ELSE',
            'cancelReason'      => 7,
            'fulfillmentResult' => 'MAYBE',
            'promotions'        => 'not-a-list',
            'baseReceipts'      => 'not-a-list',
            'purchaseMetadataMap' => 'not-a-map',
        ]);

        self::assertNull($t->getProductType());
        self::assertNull($t->getCancelReason());
        self::assertNull($t->getFulfillmentResult());
        self::assertSame([], $t->getPromotions());
        self::assertNull($t->getBaseReceipts());
        self::assertNull($t->getPurchaseMetadataMap());
    }

    public function testCanceledSubscription(): void
    {
        $t = new Transaction([
            'productType'  => 'SUBSCRIPTION',
            'cancelDate'   => self::BASE_MS + 5_000,
            'cancelReason' => 2,
            'renewalDate'  => self::BASE_MS + 9_000,
        ]);

        self::assertTrue($t->isCanceled());
        self::assertSame(CancelReason::SYSTEM_CANCELED, $t->getCancelReason());
        self::assertMs(self::BASE_MS + 5_000, $t->getExpiresAt());
        self::assertFalse($t->isEntitled(CarbonImmutable::createFromTimestampMs(self::BASE_MS)));
    }

    public function testQuickSubscribeRequiresTruthyValue(): void
    {
        self::assertFalse((new Transaction(['purchaseMetadataMap' => ['QuickSubscribe' => 'false']]))->isQuickSubscribe());
        self::assertFalse((new Transaction(['purchaseMetadataMap' => ['Other' => 'true']]))->isQuickSubscribe());
        self::assertTrue((new Transaction(['purchaseMetadataMap' => ['QuickSubscribe' => '1']]))->isQuickSubscribe());
    }

    public function testEmptyBaseReceiptsIsNotAnAddOn(): void
    {
        self::assertFalse((new Transaction(['baseReceipts' => []]))->isAddOnSubscription());
        self::assertSame([], (new Transaction(['baseReceipts' => []]))->getBaseReceipts());
    }

    public function testActivePromotionIsNullWhenNoneInProgress(): void
    {
        $t = new Transaction([
            'promotions' => [
                ['promotionType' => 'Retention Offer', 'promotionStatus' => 'Queued'],
                ['promotionType' => 'Retention Offer', 'promotionStatus' => 'Completed'],
            ],
        ]);

        self::assertCount(2, $t->getPromotions());
        self::assertNull($t->getActivePromotion());
    }

    public function testTrialAndGracePeriodAreTimeAware(): void
    {
        $now = CarbonImmutable::createFromTimestampMs(self::BASE_MS);
        $t   = new Transaction([
            'freeTrialEndDate'   => self::BASE_MS + 1_000,
            'gracePeriodEndDate' => self::BASE_MS + 1_000,
        ]);

        self::assertTrue($t->isInFreeTrial($now));
        self::assertTrue($t->isInGracePeriod($now));
        self::assertFalse($t->isInFreeTrial($now->addSeconds(2)));
        self::assertFalse($t->isInGracePeriod($now->addSeconds(2)));

        // Defaults to the current time; both dates are long in the past.
        self::assertFalse($t->isInFreeTrial());
        self::assertFalse($t->isInGracePeriod());
    }

    #[DataProvider('expiryProvider')]
    public function testExpiresAt(array $raw, ?int $expectedMs): void
    {
        self::assertMs($expectedMs, (new Transaction($raw))->getExpiresAt());
    }

    public static function expiryProvider(): iterable
    {
        $base = self::BASE_MS;

        yield 'renewal only' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base + 1],
            $base + 1,
        ];
        yield 'grace period beats renewal' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base + 1, 'gracePeriodEndDate' => $base + 2],
            $base + 2,
        ];
        yield 'cancellation beats both' => [
            [
                'productType'        => 'SUBSCRIPTION',
                'renewalDate'        => $base + 1,
                'gracePeriodEndDate' => $base + 2,
                'cancelDate'         => $base + 3,
            ],
            $base + 3,
        ];
        yield 'subscription without dates' => [
            ['productType' => 'SUBSCRIPTION'],
            null,
        ];
        yield 'entitlement never expires' => [
            ['productType' => 'ENTITLED', 'renewalDate' => $base + 1],
            null,
        ];
    }

    #[DataProvider('entitlementProvider')]
    public function testIsEntitled(array $raw, bool $expected): void
    {
        $now = CarbonImmutable::createFromTimestampMs(self::BASE_MS);

        self::assertSame($expected, (new Transaction($raw))->isEntitled($now));
    }

    public static function entitlementProvider(): iterable
    {
        $base = self::BASE_MS;

        yield 'consumable' => [['productType' => 'CONSUMABLE'], true];
        yield 'entitlement' => [['productType' => 'ENTITLED'], true];
        yield 'canceled entitlement' => [['productType' => 'ENTITLED', 'cancelDate' => $base - 1], false];
        yield 'subscription renewing in the future' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base + 1],
            true,
        ];
        yield 'subscription past its renewal date' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base - 1],
            false,
        ];
        yield 'lapsed renewal but inside grace period' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base - 1, 'gracePeriodEndDate' => $base + 1],
            true,
        ];
        yield 'grace period also lapsed' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base - 1, 'gracePeriodEndDate' => $base - 1],
            false,
        ];
        yield 'subscription without dates is valid' => [['productType' => 'SUBSCRIPTION'], true];
        yield 'canceled subscription' => [
            ['productType' => 'SUBSCRIPTION', 'renewalDate' => $base + 1, 'cancelDate' => $base - 1],
            false,
        ];
    }

    public function testIsEntitledDefaultsToNow(): void
    {
        $future = (int) CarbonImmutable::now()->addYear()->valueOf();
        $past   = (int) CarbonImmutable::now()->subYear()->valueOf();

        self::assertTrue((new Transaction(['productType' => 'SUBSCRIPTION', 'renewalDate' => $future]))->isEntitled());
        self::assertFalse((new Transaction(['productType' => 'SUBSCRIPTION', 'renewalDate' => $past]))->isEntitled());
    }

    private static function assertMs(?int $expectedMs, ?CarbonInterface $actual): void
    {
        if ($expectedMs === null) {
            self::assertNull($actual);
            return;
        }

        self::assertInstanceOf(CarbonInterface::class, $actual);
        self::assertSame($expectedMs, (int) round($actual->valueOf()));
        self::assertSame('UTC', $actual->getTimezone()->getName());
    }
}
