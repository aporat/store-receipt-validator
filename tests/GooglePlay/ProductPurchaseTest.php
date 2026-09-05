<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\GooglePlay\AcknowledgementState;
use ReceiptValidator\GooglePlay\ConsumptionState;
use ReceiptValidator\GooglePlay\ProductPurchase;
use ReceiptValidator\GooglePlay\ProductPurchaseState;
use ReceiptValidator\GooglePlay\PurchaseType;

#[CoversClass(ProductPurchase::class)]
final class ProductPurchaseTest extends TestCase
{
    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/fixtures/{$name}.json"), true);
    }

    public function testParsesAcknowledgedPurchase(): void
    {
        $data     = $this->fixture('productPurchase');
        $purchase = new ProductPurchase($data);

        self::assertSame('androidpublisher#productPurchase', $purchase->getKind());
        self::assertSame('one-time-token', $purchase->getPurchaseToken());
        self::assertSame('GPA.7777-6666-5555-44444', $purchase->getOrderId());
        self::assertSame('GPA.7777-6666-5555-44444', $purchase->getTransactionId());
        self::assertSame('app.example.coins.100', $purchase->getProductId());
        self::assertSame(2, $purchase->getQuantity());
        self::assertSame(1756911600, $purchase->getPurchaseTime()?->getTimestamp());
        self::assertSame(ProductPurchaseState::PURCHASED, $purchase->getPurchaseState());
        self::assertTrue($purchase->isPurchased());
        self::assertFalse($purchase->isPending());
        self::assertFalse($purchase->isCanceled());
        self::assertSame(ConsumptionState::YET_TO_BE_CONSUMED, $purchase->getConsumptionState());
        self::assertFalse($purchase->isConsumed());
        self::assertSame(AcknowledgementState::ACKNOWLEDGED, $purchase->getAcknowledgementState());
        self::assertTrue($purchase->isAcknowledged());
        self::assertNull($purchase->getPurchaseType());
        self::assertFalse($purchase->isTestPurchase());
        self::assertSame(Environment::PRODUCTION, $purchase->getEnvironment());
        self::assertNull($purchase->getDeveloperPayload());
        self::assertSame('ACCOUNT-1', $purchase->getObfuscatedExternalAccountId());
        self::assertNull($purchase->getObfuscatedExternalProfileId());
        self::assertSame('US', $purchase->getRegionCode());
        self::assertSame(2, $purchase->getRefundableQuantity());
        self::assertSame($data, $purchase->getRawData());
    }

    public function testParsesPendingTestPurchase(): void
    {
        $purchase = new ProductPurchase($this->fixture('productPurchaseTest'), 'token-from-lookup');

        self::assertSame('token-from-lookup', $purchase->getPurchaseToken());
        self::assertSame(1, $purchase->getQuantity());
        self::assertSame(ProductPurchaseState::PENDING, $purchase->getPurchaseState());
        self::assertTrue($purchase->isPending());
        self::assertTrue($purchase->isConsumed());
        self::assertSame(AcknowledgementState::PENDING, $purchase->getAcknowledgementState());
        self::assertFalse($purchase->isAcknowledged());
        self::assertSame(PurchaseType::TEST, $purchase->getPurchaseType());
        self::assertTrue($purchase->isTestPurchase());
        self::assertSame(Environment::SANDBOX, $purchase->getEnvironment());
    }

    public function testCanceledStateAndUnknownEnumValues(): void
    {
        $purchase = new ProductPurchase([
            'purchaseState'    => 1,
            'consumptionState' => 42,
            'purchaseType'     => 42,
        ]);

        self::assertTrue($purchase->isCanceled());
        self::assertNull($purchase->getConsumptionState());
        self::assertNull($purchase->getPurchaseType());
        self::assertSame(AcknowledgementState::UNSPECIFIED, $purchase->getAcknowledgementState());
    }

    public function testEmptyPayloadIsSafe(): void
    {
        $purchase = new ProductPurchase();

        self::assertNull($purchase->getPurchaseToken());
        self::assertNull($purchase->getPurchaseState());
        self::assertNull($purchase->getPurchaseTime());
        self::assertFalse($purchase->isPurchased());
        self::assertSame(Environment::PRODUCTION, $purchase->getEnvironment());
    }
}
