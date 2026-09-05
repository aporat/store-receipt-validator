<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\GooglePlay\VoidedPurchase;
use ReceiptValidator\GooglePlay\VoidedPurchasesParams;
use ReceiptValidator\GooglePlay\VoidedPurchasesResponse;
use ReceiptValidator\GooglePlay\VoidedPurchaseType;
use ReceiptValidator\GooglePlay\VoidedReason;
use ReceiptValidator\GooglePlay\VoidedSource;

#[CoversClass(VoidedPurchasesResponse::class)]
#[CoversClass(VoidedPurchase::class)]
#[CoversClass(VoidedPurchasesParams::class)]
final class VoidedPurchasesTest extends TestCase
{
    public function testParsesResponsePage(): void
    {
        $data     = json_decode((string) file_get_contents(__DIR__ . '/fixtures/voidedPurchases.json'), true);
        $response = new VoidedPurchasesResponse($data, Environment::SANDBOX);

        self::assertSame(Environment::SANDBOX, $response->getEnvironment());
        self::assertSame('next-page', $response->getNextPageToken());
        self::assertSame('prev-page', $response->getPreviousPageToken());
        self::assertTrue($response->hasMore());
        self::assertSame(2, $response->getTotalResults());
        self::assertSame(2, $response->getResultPerPage());
        self::assertSame(0, $response->getStartIndex());
        self::assertSame($response->getTransactions(), $response->getVoidedPurchases());
        self::assertCount(2, $response->getVoidedPurchases());

        [$first, $second] = $response->getVoidedPurchases();

        self::assertSame('androidpublisher#voidedPurchase', $first->getKind());
        self::assertSame('voided-token-1', $first->getPurchaseToken());
        self::assertSame('GPA.1000-2000-3000-40000', $first->getOrderId());
        self::assertSame('GPA.1000-2000-3000-40000', $first->getTransactionId());
        self::assertNull($first->getProductId());
        self::assertSame(1, $first->getQuantity());
        self::assertSame(1756825200, $first->getPurchaseTime()?->getTimestamp());
        self::assertSame(1756911600, $first->getVoidedTime()?->getTimestamp());
        self::assertSame(VoidedSource::DEVELOPER, $first->getVoidedSource());
        self::assertSame(VoidedReason::CHARGEBACK, $first->getVoidedReason());
        self::assertNull($first->getVoidedQuantity());
        self::assertSame($data['voidedPurchases'][0], $first->getRawData());

        self::assertSame(VoidedSource::USER, $second->getVoidedSource());
        self::assertNull($second->getVoidedReason(), 'Unknown reason codes map to null');
        self::assertSame(3, $second->getVoidedQuantity());
        self::assertSame(3, $second->getQuantity());
    }

    public function testEmptyResponse(): void
    {
        $response = new VoidedPurchasesResponse(['voidedPurchases' => ['garbage']]);

        self::assertSame([], $response->getVoidedPurchases());
        self::assertFalse($response->hasMore());
        self::assertNull($response->getNextPageToken());
        self::assertNull($response->getTotalResults());

        $purchase = new VoidedPurchase();
        self::assertNull($purchase->getVoidedSource());
        self::assertNull($purchase->getVoidedReason());
        self::assertNull($purchase->getPurchaseTime());
    }

    public function testParamsDefaultToEmptyQuery(): void
    {
        self::assertSame([], (new VoidedPurchasesParams())->toQueryParams());
    }

    public function testParamsSerialiseEveryField(): void
    {
        $params = new VoidedPurchasesParams(
            startTime: CarbonImmutable::createFromTimestampMs(1756825200123),
            endTime: CarbonImmutable::createFromTimestampMs(1756911600456),
            maxResults: 50,
            startIndex: 100,
            token: 'abc',
            type: VoidedPurchaseType::INCLUDE_SUBSCRIPTIONS,
            includeQuantityBasedPartialRefund: true,
        );

        self::assertSame([
            'startTime'                         => 1756825200123,
            'endTime'                           => 1756911600456,
            'maxResults'                        => 50,
            'startIndex'                        => 100,
            'token'                             => 'abc',
            'type'                              => 1,
            'includeQuantityBasedPartialRefund' => 'true',
        ], $params->toQueryParams());
    }

    public function testParamsOmitEmptyTokenAndSerialiseFalse(): void
    {
        $params = new VoidedPurchasesParams(token: '', includeQuantityBasedPartialRefund: false);

        self::assertSame(['includeQuantityBasedPartialRefund' => 'false'], $params->toQueryParams());
    }
}
