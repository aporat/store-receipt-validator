<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Response;
use ReceiptValidator\Amazon\Transaction;
use ReceiptValidator\Environment;

/**
 * @group amazon
 */
#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    #[DataProvider('responseProvider')]
    public function testResponseParsing(array $raw, array $expected, Environment $env): void
    {
        $r = new Response($raw, $env);

        // Raw + env
        self::assertSame($raw, $r->getRawData());
        self::assertSame($expected['environment'], $r->getEnvironment());

        // Scalars
        self::assertSame($expected['receiptId'],       $r->getReceiptId());
        self::assertSame($expected['productId'],       $r->getProductId());
        self::assertSame($expected['userId'],          $r->getUserId());
        self::assertSame($expected['productType'],     $r->getProductType());
        self::assertSame($expected['testTransaction'], $r->isTestTransaction());

        // Dates (millis or null)
        self::assertCarbonMsOrNull($expected['purchaseDateMs'],    $r->getPurchaseDate());
        self::assertCarbonMsOrNull($expected['cancellationDateMs'], $r->getCancellationDate());

        // Transactions list
        $txs = $r->getTransactions();
        self::assertCount($expected['txCount'], $txs);
        if ($expected['txCount'] > 0) {
            self::assertInstanceOf(Transaction::class, $txs[0]);
        }
    }

    private static function assertCarbonMsOrNull(?int $expectedMs, ?CarbonInterface $actual): void
    {
        if ($expectedMs === null) {
            self::assertNull($actual);
            return;
        }
        self::assertInstanceOf(CarbonInterface::class, $actual);
        $actualMs = (int) round($actual->valueOf());
        self::assertSame($expectedMs, $actualMs);
    }

    public static function responseProvider(): array
    {
        $purchaseMs = 1_609_459_200_000; // 2021-01-01T00:00:00Z
        $cancelMs   = 1_612_137_600_000; // 2021-02-01T00:00:00Z

        return [
            'valid_no_cancel' => [
                // 0 => raw
                [
                    'receiptId'       => 'txn_001',
                    'productId'       => 'com.amazon.test.product',
                    'userId'          => 'amzn1.account.testuser',
                    'productType'     => 'CONSUMABLE',
                    'purchaseDate'    => $purchaseMs,
                    'testTransaction' => true,
                ],
                // 1 => expected
                [
                    'environment'        => Environment::PRODUCTION,
                    'receiptId'          => 'txn_001',
                    'productId'          => 'com.amazon.test.product',
                    'userId'             => 'amzn1.account.testuser',
                    'productType'        => 'CONSUMABLE',
                    'testTransaction'    => true,
                    'purchaseDateMs'     => $purchaseMs,
                    'cancellationDateMs' => null,
                    'txCount'            => 1,
                ],
                // 2 => env (positional)
                Environment::PRODUCTION,
            ],
            'valid_with_cancel' => [
                [
                    'receiptId'       => 'txn_002',
                    'productId'       => 'com.amazon.test.subscription',
                    'purchaseDate'    => $purchaseMs,
                    'cancelDate'      => $cancelMs,
                    'productType'     => 'SUBSCRIPTION',
                    'testTransaction' => false,
                ],
                [
                    'environment'        => Environment::PRODUCTION,
                    'receiptId'          => 'txn_002',
                    'productId'          => 'com.amazon.test.subscription',
                    'userId'             => null,
                    'productType'        => 'SUBSCRIPTION',
                    'testTransaction'    => false,
                    'purchaseDateMs'     => $purchaseMs,
                    'cancellationDateMs' => $cancelMs,
                    'txCount'            => 1,
                ],
                Environment::PRODUCTION,
            ],
            'empty' => [
                [],
                [
                    'environment'        => Environment::PRODUCTION,
                    'receiptId'          => null,
                    'productId'          => null,
                    'userId'             => null,
                    'productType'        => null,
                    'testTransaction'    => false,
                    'purchaseDateMs'     => null,
                    'cancellationDateMs' => null,
                    'txCount'            => 0,
                ],
                Environment::PRODUCTION,
            ],
        ];
    }
}
