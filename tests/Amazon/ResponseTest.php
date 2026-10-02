<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\CancelReason;
use ReceiptValidator\Amazon\ProductType;
use ReceiptValidator\Amazon\Promotion;
use ReceiptValidator\Amazon\Response;
use ReceiptValidator\Amazon\Transaction;
use ReceiptValidator\Environment;

/**
 * @group amazon
 */
#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    private const int PURCHASE_MS = 1_609_459_200_000; // 2021-01-01T00:00:00Z
    private const int CANCEL_MS   = 1_612_137_600_000; // 2021-02-01T00:00:00Z

    public function testValidConsumableResponse(): void
    {
        $raw = [
            'receiptId'       => 'txn_001',
            'productId'       => 'com.amazon.test.product',
            'productType'     => 'CONSUMABLE',
            'purchaseDate'    => self::PURCHASE_MS,
            'countryCode'     => 'US',
            'betaProduct'     => true,
            'testTransaction' => true,
        ];

        $r = new Response($raw, Environment::SANDBOX, 'amzn1.account.testuser');

        self::assertSame($raw, $r->getRawData());
        self::assertSame(Environment::SANDBOX, $r->getEnvironment());
        self::assertSame('amzn1.account.testuser', $r->getUserId());
        self::assertSame('txn_001', $r->getReceiptId());
        self::assertSame('com.amazon.test.product', $r->getProductId());
        self::assertSame(ProductType::CONSUMABLE, $r->getProductType());
        self::assertSame(self::PURCHASE_MS, (int) $r->getPurchaseDate()?->valueOf());
        self::assertNull($r->getCancellationDate());
        self::assertNull($r->getCancelReason());
        self::assertTrue($r->isTestTransaction());
        self::assertTrue($r->isBetaProduct());
        self::assertSame('US', $r->getCountryCode());
        self::assertSame([], $r->getPromotions());
        self::assertFalse($r->isQuickSubscribe());
        self::assertFalse($r->isCanceled());
        self::assertNull($r->getExpiresAt());
        self::assertTrue($r->isEntitled());

        $transactions = $r->getTransactions();
        self::assertCount(1, $transactions);
        self::assertInstanceOf(Transaction::class, $transactions[0]);
        self::assertSame($transactions[0], $r->getTransaction());
        self::assertSame('txn_001', $transactions[0]->getTransactionId());
    }

    public function testCanceledSubscriptionDelegatesToTransaction(): void
    {
        $raw = [
            'receiptId'           => 'txn_002',
            'productId'           => 'com.amazon.test.subscription',
            'productType'         => 'SUBSCRIPTION',
            'purchaseDate'        => self::PURCHASE_MS,
            'cancelDate'          => self::CANCEL_MS,
            'cancelReason'        => 1,
            'renewalDate'         => self::CANCEL_MS + 1_000,
            'promotions'          => [['promotionType' => 'Retention Offer', 'promotionStatus' => 'InProgress']],
            'purchaseMetadataMap' => ['QuickSubscribe' => 'true'],
            'testTransaction'     => false,
        ];

        $r = new Response($raw, Environment::PRODUCTION);

        self::assertNull($r->getUserId());
        self::assertSame(ProductType::SUBSCRIPTION, $r->getProductType());
        self::assertSame(self::CANCEL_MS, (int) $r->getCancellationDate()?->valueOf());
        self::assertSame(CancelReason::CUSTOMER_CANCELED, $r->getCancelReason());
        self::assertTrue($r->isCanceled());
        self::assertSame(self::CANCEL_MS, (int) $r->getExpiresAt()?->valueOf());
        self::assertFalse($r->isEntitled(CarbonImmutable::createFromTimestampMs(self::PURCHASE_MS)));
        self::assertFalse($r->isTestTransaction());
        self::assertFalse($r->isBetaProduct());
        self::assertNull($r->getCountryCode());
        self::assertContainsOnlyInstancesOf(Promotion::class, $r->getPromotions());
        self::assertCount(1, $r->getPromotions());
        self::assertTrue($r->isQuickSubscribe());
    }

    public function testUserIdFallsBackToBodyWhenNotSupplied(): void
    {
        $r = new Response(['receiptId' => 'txn', 'userId' => 'from-body']);

        self::assertSame('from-body', $r->getUserId());
        self::assertSame('override', (new Response(['userId' => 'from-body'], userId: 'override'))->getUserId());
    }

    public function testEmptyResponseHasNoTransaction(): void
    {
        $r = new Response([], Environment::PRODUCTION);

        self::assertSame(Environment::PRODUCTION, $r->getEnvironment());
        self::assertNull($r->getTransaction());
        self::assertSame([], $r->getTransactions());
        self::assertNull($r->getReceiptId());
        self::assertNull($r->getProductId());
        self::assertNull($r->getUserId());
        self::assertNull($r->getProductType());
        self::assertNull($r->getPurchaseDate());
        self::assertNull($r->getCancellationDate());
        self::assertNull($r->getCancelReason());
        self::assertFalse($r->isTestTransaction());
        self::assertFalse($r->isBetaProduct());
        self::assertNull($r->getCountryCode());
        self::assertSame([], $r->getPromotions());
        self::assertFalse($r->isQuickSubscribe());
        self::assertFalse($r->isCanceled());
        self::assertNull($r->getExpiresAt());
        self::assertFalse($r->isEntitled());
    }
}
