<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\Transaction;

/**
 * @group itunes
 */
#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    #[DataProvider('transactionDataProvider')]
    public function testTransactionIsCreatedCorrectly(array $raw, array $expected): void
    {
        $t = new Transaction($raw);

        // Scalars
        self::assertSame($expected['quantity'],             $t->getQuantity());
        self::assertSame($expected['transactionId'],        $t->getTransactionId());
        self::assertSame($expected['productId'],            $t->getProductId());
        self::assertSame($expected['originalTransactionId'],$t->getOriginalTransactionId());
        self::assertSame($expected['webOrderLineItemId'],   $t->getWebOrderLineItemId());
        self::assertSame($expected['promotionalOfferId'],   $t->getPromotionalOfferId());
        self::assertSame($expected['isTrialPeriod'],        $t->isTrialPeriod());
        self::assertSame($expected['isInIntroOfferPeriod'], $t->isInIntroOfferPeriod());
        self::assertSame($expected['hasExpired'],           $t->hasExpired());
        self::assertSame($expected['wasCanceled'],          $t->wasCanceled());

        // Dates
        self::assertCarbonMsOrNull($expected['purchaseDateMs'] ?? null,  $t->getPurchaseDate());
        self::assertCarbonMsOrNull($expected['originalDateMs'] ?? null,  $t->getOriginalPurchaseDate());
        self::assertCarbonMsOrNull($expected['expiresDateMs'] ?? null,   $t->getExpiresDate());
        self::assertCarbonMsOrNull($expected['cancelDateMs'] ?? null,    $t->getCancellationDate());
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
        $actualMs = (int) round($actual->valueOf());
        self::assertSame($expectedMs, $actualMs);
    }

    public static function transactionDataProvider(): array
    {
        $baseMs = 1_609_459_200_000; // 2021-01-01T00:00:00Z
        return [
            'fully_populated' => [
                'raw' => [
                    'quantity'                   => '1',
                    'transaction_id'             => 'tx123',
                    'product_id'                 => 'com.example.product',
                    'original_transaction_id'    => 'otx456',
                    'web_order_line_item_id'     => 'line123',
                    'promotional_offer_id'       => 'promo789',
                    'is_trial_period'            => 'true',
                    'is_in_intro_offer_period'   => 'false',
                    'purchase_date_ms'           => $baseMs,
                    'original_purchase_date_ms'  => $baseMs - 86_400_000,
                    'expires_date_ms'            => $baseMs - 1_000,  // expired
                    'cancellation_date_ms'       => $baseMs + 172_800_000,
                ],
                'expected' => [
                    'quantity'             => 1,
                    'transactionId'        => 'tx123',
                    'productId'            => 'com.example.product',
                    'originalTransactionId'=> 'otx456',
                    'webOrderLineItemId'   => 'line123',
                    'promotionalOfferId'   => 'promo789',
                    'isTrialPeriod'        => true,
                    'isInIntroOfferPeriod' => false,
                    'hasExpired'           => true,
                    'wasCanceled'          => true,
                    'purchaseDateMs'       => $baseMs,
                    'originalDateMs'       => $baseMs - 86_400_000,
                    'expiresDateMs'        => $baseMs - 1_000,
                    'cancelDateMs'         => $baseMs + 172_800_000,
                ],
            ],
            'empty_data' => [
                'raw' => [],
                'expected' => [
                    'quantity'             => 0,
                    'transactionId'        => null,
                    'productId'            => null,
                    'originalTransactionId'=> null,
                    'webOrderLineItemId'   => null,
                    'promotionalOfferId'   => null,
                    'isTrialPeriod'        => false,
                    'isInIntroOfferPeriod' => false,
                    'hasExpired'           => false,
                    'wasCanceled'          => false,
                    'purchaseDateMs'       => null,
                    'originalDateMs'       => null,
                    'expiresDateMs'        => null,
                    'cancelDateMs'         => null,
                ],
            ],
        ];
    }
}
