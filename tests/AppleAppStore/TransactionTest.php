<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\CarbonInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Environment;

/**
 * @group apple-app-store
 */
#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    #[DataProvider('transactionDataProvider')]
    public function testTransactionIsCreatedCorrectly(array $raw, array $expected): void
    {
        $t = new Transaction($raw);

        // Parent/common fields
        self::assertSame($expected['quantity'],        $t->getQuantity());
        self::assertSame($expected['transactionId'],   $t->getTransactionId());
        self::assertSame($expected['productId'],       $t->getProductId());

        // Identifiers & misc scalars
        self::assertSame($expected['originalTransactionId'], $t->getOriginalTransactionId());
        self::assertSame($expected['webOrderLineItemId'],    $t->getWebOrderLineItemId());
        self::assertSame($expected['bundleId'],              $t->getBundleId());
        self::assertSame($expected['subscriptionGroupId'],   $t->getSubscriptionGroupIdentifier());
        self::assertSame($expected['type'],                  $t->getType());
        self::assertSame($expected['appAccountToken'],       $t->getAppAccountToken());
        self::assertSame($expected['inAppOwnershipType'],    $t->getInAppOwnershipType());
        self::assertSame($expected['revocationReason'],      $t->getRevocationReason());
        self::assertSame($expected['offerType'],             $t->getOfferType());
        self::assertSame($expected['offerIdentifier'],       $t->getOfferIdentifier());
        self::assertSame($expected['storefront'],            $t->getStorefront());
        self::assertSame($expected['storefrontId'],          $t->getStorefrontId());
        self::assertSame($expected['transactionReason'],     $t->getTransactionReason());
        self::assertSame($expected['currency'],              $t->getCurrency());
        self::assertSame($expected['price'],                 $t->getPrice());
        self::assertSame($expected['offerDiscountType'],     $t->getOfferDiscountType());
        self::assertSame($expected['appTransactionId'],      $t->getAppTransactionId());
        self::assertSame($expected['offerPeriod'],           $t->getOfferPeriod());
        self::assertSame($expected['isUpgraded'],            $t->isUpgraded());

        // Dates — compare by ms epoch (exact), or null
        self::assertCarbonMsOrNull($expected['purchaseDateMs'] ?? null,         $t->getPurchaseDate());
        self::assertCarbonMsOrNull($expected['originalPurchaseDateMs'] ?? null, $t->getOriginalPurchaseDate());
        self::assertCarbonMsOrNull($expected['expiresDateMs'] ?? null,          $t->getExpiresDate());
        self::assertCarbonMsOrNull($expected['signedDateMs'] ?? null,           $t->getSignedDate());
        self::assertCarbonMsOrNull($expected['revocationDateMs'] ?? null,       $t->getRevocationDate());

        // Environment & raw
        self::assertSame($expected['environment'], $t->getEnvironment());
        self::assertSame($raw, $t->getRawData());
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
        // Build one "full" case with stable millis
        $baseMs = 1_609_459_200_000; // 2021-01-01T00:00:00Z, fixed for deterministic tests

        return [
            'fully_populated' => [
                'raw' => [
                    'originalTransactionId'       => '1000000000000000',
                    'transactionId'               => '2000000000000000',
                    'webOrderLineItemId'          => '3000000000000000',
                    'bundleId'                    => 'com.example.app',
                    'productId'                   => 'com.example.product1',
                    'subscriptionGroupIdentifier' => 'group.com.example',
                    'quantity'                    => '1',
                    'type'                        => 'Auto-Renewable Subscription',
                    'appAccountToken'             => 'abc-123-def-456',
                    'inAppOwnershipType'          => 'PURCHASED',
                    'revocationReason'            => '1',
                    'isUpgraded'                  => 'true',
                    'offerType'                   => 'Intro',
                    'offerIdentifier'             => 'intro-123',
                    'storefront'                  => 'USA',
                    'storefrontId'                => '143441',
                    'transactionReason'           => 'PURCHASE',
                    'currency'                    => 'USD',
                    'price'                       => '999',
                    'offerDiscountType'           => 'PAY_AS_YOU_GO',
                    'appTransactionId'            => 'app-transaction-id-1',
                    'offerPeriod'                 => 'P1M',
                    'environment'                 => 'Production',
                    'purchaseDate'                => $baseMs,
                    'originalPurchaseDate'        => $baseMs - 86_400_000,
                    'expiresDate'                 => $baseMs + 86_400_000,
                    'signedDate'                  => $baseMs + 1_000,
                    'revocationDate'              => $baseMs + 2_000,
                ],
                'expected' => [
                    'originalTransactionId' => '1000000000000000',
                    'transactionId'         => '2000000000000000',
                    'webOrderLineItemId'    => '3000000000000000',
                    'bundleId'              => 'com.example.app',
                    'productId'             => 'com.example.product1',
                    'subscriptionGroupId'   => 'group.com.example',
                    'quantity'              => 1,
                    'type'                  => 'Auto-Renewable Subscription',
                    'appAccountToken'       => 'abc-123-def-456',
                    'inAppOwnershipType'    => 'PURCHASED',
                    'revocationReason'      => '1',
                    'isUpgraded'            => true,
                    'offerType'             => 'Intro',
                    'offerIdentifier'       => 'intro-123',
                    'storefront'            => 'USA',
                    'storefrontId'          => '143441',
                    'transactionReason'     => 'PURCHASE',
                    'currency'              => 'USD',
                    'price'                 => 999,
                    'offerDiscountType'     => 'PAY_AS_YOU_GO',
                    'appTransactionId'      => 'app-transaction-id-1',
                    'offerPeriod'           => 'P1M',
                    'purchaseDateMs'        => $baseMs,
                    'originalPurchaseDateMs'=> $baseMs - 86_400_000,
                    'expiresDateMs'         => $baseMs + 86_400_000,
                    'signedDateMs'          => $baseMs + 1_000,
                    'revocationDateMs'      => $baseMs + 2_000,
                    'environment'           => Environment::PRODUCTION,
                ],
            ],
            'empty_data' => [
                'raw' => [],
                'expected' => [
                    'originalTransactionId' => null,
                    'transactionId'         => null,
                    'webOrderLineItemId'    => null,
                    'bundleId'              => null,
                    'productId'             => null,
                    'subscriptionGroupId'   => null,
                    'quantity'              => 1,
                    'type'                  => null,
                    'appAccountToken'       => null,
                    'inAppOwnershipType'    => null,
                    'revocationReason'      => null,
                    'isUpgraded'            => false,
                    'offerType'             => null,
                    'offerIdentifier'       => null,
                    'storefront'            => null,
                    'storefrontId'          => null,
                    'transactionReason'     => null,
                    'currency'              => null,
                    'price'                 => null,
                    'offerDiscountType'     => null,
                    'appTransactionId'      => null,
                    'offerPeriod'           => null,
                    'purchaseDateMs'        => null,
                    'originalPurchaseDateMs'=> null,
                    'expiresDateMs'         => null,
                    'signedDateMs'          => null,
                    'revocationDateMs'      => null,
                    'environment'           => Environment::PRODUCTION,
                ],
            ],
        ];
    }
}
