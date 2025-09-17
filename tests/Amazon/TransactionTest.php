<?php

namespace ReceiptValidator\Tests\Amazon;

use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Transaction;

/**
 * @group amazon
 */
#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    #[DataProvider('transactionDataProvider')]
    public function testTransactionIsCreatedCorrectly(array $rawData, array $expected): void
    {
        $t = new Transaction($rawData);

        // Parent props
        self::assertSame($expected['quantity'], $t->getQuantity());
        self::assertSame($expected['productId'], $t->getProductId());
        self::assertSame($expected['transactionId'], $t->getTransactionId());
        self::assertSame($expected['autoRenewing'], $t->isAutoRenewing());
        self::assertSame($expected['term'], $t->getTerm());
        self::assertSame($expected['termSku'], $t->getTermSku());

        // Dates — compare by millisecond epoch (exact), or null
        self::assertCarbonMsOrNull($expected['purchaseDateMs'] ?? null, $t->getPurchaseDate());
        self::assertCarbonMsOrNull($expected['cancelDateMs'] ?? null, $t->getCancellationDate());
        self::assertCarbonMsOrNull($expected['renewalDateMs'] ?? null, $t->getRenewalDate());
        self::assertCarbonMsOrNull($expected['graceEndMs'] ?? null, $t->getGracePeriodEndDate());
        self::assertCarbonMsOrNull($expected['trialEndMs'] ?? null, $t->getFreeTrialEndDate());
    }

    /**
     * Assert a Carbon date has the exact expected millisecond epoch, or both are null.
     */
    private static function assertCarbonMsOrNull(?int $expectedMs, ?CarbonInterface $actual): void
    {
        if ($expectedMs === null) {
            self::assertNull($actual);
            return;
        }

        self::assertInstanceOf(CarbonInterface::class, $actual);
        // Normalize to int to avoid float/int mismatches from valueOf()
        $actualMs = (int) round($actual->valueOf());
        self::assertSame($expectedMs, $actualMs);
    }

    public static function transactionDataProvider(): array
    {
        $baseMs = 1609459200000; // 2021-01-01T00:00:00Z in ms

        return [
            'fully_populated_data' => [
                'rawData' => [
                    'productId'          => 'com.amazon.sample',
                    'receiptId'          => 'txn123',
                    'quantity'           => 1,
                    'purchaseDate'       => $baseMs,
                    'cancelDate'         => $baseMs + 2678400000,
                    'renewalDate'        => $baseMs + 5284800000,
                    'GracePeriodEndDate' => $baseMs + 5376000000,
                    'freeTrialEndDate'   => $baseMs + 5466000000,
                    'AutoRenewing'       => true,
                    'term'               => '1 Month',
                    'termSku'            => 'sub1-monthly',
                ],
                'expected' => [
                    'quantity'       => 1,
                    'productId'      => 'com.amazon.sample',
                    'transactionId'  => 'txn123',
                    'autoRenewing'   => true,
                    'term'           => '1 Month',
                    'termSku'        => 'sub1-monthly',
                    'purchaseDateMs' => $baseMs,
                    'cancelDateMs'   => $baseMs + 2678400000,
                    'renewalDateMs'  => $baseMs + 5284800000,
                    'graceEndMs'     => $baseMs + 5376000000,
                    'trialEndMs'     => $baseMs + 5466000000,
                ],
            ],
            'empty_data' => [
                'rawData' => [],
                'expected' => [
                    'quantity'       => 1,
                    'productId'      => null,
                    'transactionId'  => null,
                    'autoRenewing'   => false, // default(false) from helper
                    'term'           => null,
                    'termSku'        => null,
                    'purchaseDateMs' => null,
                    'cancelDateMs'   => null,
                    'renewalDateMs'  => null,
                    'graceEndMs'     => null,
                    'trialEndMs'     => null,
                ],
            ],
        ];
    }
}
