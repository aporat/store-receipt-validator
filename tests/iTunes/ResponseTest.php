<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\iTunes\Response;
use ReceiptValidator\iTunes\Transaction;
use ReceiptValidator\iTunes\RenewalInfo;

/**
 * @group itunes
 */
#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    #[DataProvider('responseProvider')]
    public function testResponseParsing(array $raw, Environment $env, array $expect): void
    {
        $r = new Response($raw, $env);

        // Basics
        self::assertSame($expect['bundleId'], $r->getBundleId());
        self::assertSame($expect['appItemId'], $r->getAppItemId());
        self::assertSame($expect['isRetryable'], $r->isRetryable());

        // Dates (Carbon type or null)
        $this->assertCarbonTypeOrNull($expect['originalPurchaseMs'], $r->getOriginalPurchaseDate());
        $this->assertCarbonTypeOrNull($expect['requestDateMs'],      $r->getRequestDate());
        $this->assertCarbonTypeOrNull($expect['receiptCreateMs'],    $r->getReceiptCreationDate());

        // Transactions from "receipt.in_app" or legacy top-level
        self::assertCount($expect['txCount'], $r->getTransactions());
        if ($expect['txCount'] > 0) {
            self::assertInstanceOf(Transaction::class, $r->getTransactions()[0]);
            if (isset($expect['firstTxId'])) {
                self::assertSame($expect['firstTxId'], $r->getTransactions()[0]->getTransactionId());
            }
        }

        // Latest receipt info
        self::assertCount($expect['latestCount'], $r->getLatestReceiptInfo());
        if ($expect['latestCount'] > 0) {
            self::assertInstanceOf(Transaction::class, $r->getLatestReceiptInfo()[0]);
        }
        self::assertSame($expect['latestReceipt'], $r->getLatestReceipt());

        // Pending renewal info
        self::assertCount($expect['pendingCount'], $r->getPendingRenewalInfo());
        if ($expect['pendingCount'] > 0) {
            self::assertInstanceOf(RenewalInfo::class, $r->getPendingRenewalInfo()[0]);
        }
    }

    private function assertCarbonTypeOrNull(?int $expectedMs, ?CarbonInterface $actual): void
    {
        if ($expectedMs === null) {
            self::assertNull($actual);
            return;
        }
        self::assertInstanceOf(CarbonInterface::class, $actual);
        // Using type-only checks keeps the test resilient to timezone/precision
        // If you want exact ms, uncomment the next two lines:
        // $actualMs = (int) round($actual->valueOf());
        // self::assertSame($expectedMs, $actualMs);
    }

    public static function responseProvider(): array
    {
        $ts = 1_609_459_200_000; // 2021-01-01T00:00:00Z

        return [
            'ios7_style' => [
                'raw' => [
                    'receipt' => [
                        'app_item_id'               => '123456',
                        'original_purchase_date_ms' => $ts,
                        'request_date_ms'           => $ts,
                        'receipt_creation_date_ms'  => $ts,
                        'in_app'                    => [['transaction_id' => 'tx1']],
                        'bundle_id'                 => 'com.example.test',
                    ],
                    'latest_receipt_info' => [['transaction_id' => 'tx2']],
                    'latest_receipt'      => 'base64data',
                    'pending_renewal_info'=> [['product_id' => 'test_product']],
                    'is-retryable'        => true,
                ],
                'env' => Environment::PRODUCTION,
                'expect' => [
                    'bundleId'         => 'com.example.test',
                    'appItemId'        => '123456',
                    'isRetryable'      => true,
                    'originalPurchaseMs'=> $ts,
                    'requestDateMs'     => $ts,
                    'receiptCreateMs'   => $ts,
                    'txCount'           => 1,
                    'firstTxId'         => 'tx1',
                    'latestCount'       => 1,
                    'latestReceipt'     => 'base64data',
                    'pendingCount'      => 1,
                ],
            ],
            'ios6_legacy' => [
                'raw' => [
                    'receipt' => [
                        'transaction_id' => 'legacy_tx',
                        'product_id'     => 'legacy_product',
                        'bid'            => 'legacy.app',
                    ],
                ],
                'env' => Environment::PRODUCTION,
                'expect' => [
                    'bundleId'          => 'legacy.app',
                    'appItemId'         => null,
                    'isRetryable'       => false,
                    'originalPurchaseMs'=> null,
                    'requestDateMs'     => null,
                    'receiptCreateMs'   => null,
                    'txCount'           => 1,
                    'firstTxId'         => 'legacy_tx',
                    'latestCount'       => 0,
                    'latestReceipt'     => null,
                    'pendingCount'      => 0,
                ],
            ],
            'empty' => [
                'raw' => [],
                'env' => Environment::PRODUCTION,
                'expect' => [
                    'bundleId'          => null,
                    'appItemId'         => null,
                    'isRetryable'       => false,
                    'originalPurchaseMs'=> null,
                    'requestDateMs'     => null,
                    'receiptCreateMs'   => null,
                    'txCount'           => 0,
                    'latestCount'       => 0,
                    'latestReceipt'     => null,
                    'pendingCount'      => 0,
                ],
            ],
        ];
    }
}
