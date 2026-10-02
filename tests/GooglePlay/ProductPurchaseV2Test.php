<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\GooglePlay\AcknowledgementState;
use ReceiptValidator\GooglePlay\ConsumptionState;
use ReceiptValidator\GooglePlay\ProductLineItem;
use ReceiptValidator\GooglePlay\ProductPurchaseState;
use ReceiptValidator\GooglePlay\ProductPurchaseV2;

#[CoversClass(ProductPurchaseV2::class)]
#[CoversClass(ProductLineItem::class)]
final class ProductPurchaseV2Test extends TestCase
{
    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/fixtures/{$name}.json"), true);
    }

    public function testParsesMultiItemPurchase(): void
    {
        $data     = $this->fixture('productPurchaseV2');
        $purchase = new ProductPurchaseV2($data, 'lookup-token');

        self::assertSame('androidpublisher#productPurchaseV2', $purchase->getKind());
        self::assertSame('lookup-token', $purchase->getPurchaseToken());
        self::assertSame('GPA.7777-6666-5555-44444', $purchase->getOrderId());
        self::assertSame(ProductPurchaseState::PURCHASED, $purchase->getPurchaseState());
        self::assertTrue($purchase->isPurchased());
        self::assertFalse($purchase->isPending());
        self::assertFalse($purchase->isCanceled());
        self::assertSame(AcknowledgementState::ACKNOWLEDGED, $purchase->getAcknowledgementState());
        self::assertTrue($purchase->isAcknowledged());
        self::assertFalse($purchase->isTestPurchase());
        self::assertSame(Environment::PRODUCTION, $purchase->getEnvironment());
        self::assertSame('ACCOUNT-1', $purchase->getObfuscatedExternalAccountId());
        self::assertNull($purchase->getObfuscatedExternalProfileId());
        self::assertSame('US', $purchase->getRegionCode());
        self::assertSame('2025-09-03T15:00:00+00:00', $purchase->getPurchaseCompletionTime()?->toIso8601String());
        self::assertSame(['app.example.coins.100', 'app.example.movie'], $purchase->getProductIds());
        self::assertFalse($purchase->isConsumed());
        self::assertSame($data, $purchase->getRawData());

        $items = $purchase->getLineItems();
        self::assertCount(2, $items);
        self::assertSame($items, $purchase->getTransactions());

        $coins = $items[0];
        self::assertSame('app.example.coins.100', $coins->getProductId());
        self::assertSame('GPA.7777-6666-5555-44444', $coins->getTransactionId());
        self::assertSame(2, $coins->getQuantity());
        self::assertSame(2, $coins->getRefundableQuantity());
        self::assertSame('coins-launch', $coins->getOfferId());
        self::assertSame('default', $coins->getPurchaseOptionId());
        self::assertSame('offer-token-1', $coins->getOfferToken());
        self::assertSame(['launch'], $coins->getOfferTags());
        self::assertSame(ConsumptionState::YET_TO_BE_CONSUMED, $coins->getConsumptionState());
        self::assertFalse($coins->isConsumed());
        self::assertFalse($coins->isRental());
        self::assertFalse($coins->isPreorder());
        self::assertNull($coins->getPreorderReleaseTime());

        $movie = $items[1];
        self::assertSame('app.example.movie', $movie->getProductId());
        self::assertSame(1, $movie->getQuantity());
        self::assertNull($movie->getOfferId());
        self::assertSame([], $movie->getOfferTags());
        self::assertTrue($movie->isRental());
        self::assertTrue($movie->isConsumed());
    }

    public function testParsesPendingTestPreorder(): void
    {
        $purchase = new ProductPurchaseV2($this->fixture('productPurchaseV2Preorder'));

        self::assertNull($purchase->getPurchaseToken());
        self::assertTrue($purchase->isPending());
        self::assertFalse($purchase->isPurchased());
        self::assertTrue($purchase->isTestPurchase());
        self::assertSame(Environment::SANDBOX, $purchase->getEnvironment());
        self::assertSame(AcknowledgementState::PENDING, $purchase->getAcknowledgementState());
        self::assertFalse($purchase->isAcknowledged());
        self::assertNull($purchase->getPurchaseCompletionTime());
        self::assertNull($purchase->getRegionCode());

        $item = $purchase->getLineItems()[0];
        self::assertTrue($item->isPreorder());
        self::assertSame('2026-12-01T00:00:00+00:00', $item->getPreorderReleaseTime()?->toIso8601String());
        self::assertNull($item->getConsumptionState());
        self::assertFalse($item->isConsumed());
        self::assertFalse($purchase->isConsumed());
    }

    public function testHandlesEmptyAndUnknownValues(): void
    {
        $purchase = new ProductPurchaseV2([
            'purchaseStateContext' => ['purchaseState' => 'SOMETHING_NEW'],
            'productLineItem'      => ['not-an-item', ['productId' => 'x']],
        ]);

        self::assertNull($purchase->getKind());
        self::assertNull($purchase->getOrderId());
        self::assertNull($purchase->getPurchaseState());
        self::assertFalse($purchase->isPurchased());
        self::assertSame(AcknowledgementState::UNSPECIFIED, $purchase->getAcknowledgementState());
        self::assertCount(1, $purchase->getLineItems());
        self::assertSame(['x'], $purchase->getProductIds());
        self::assertSame(1, $purchase->getLineItems()[0]->getQuantity());

        self::assertFalse((new ProductPurchaseV2([]))->isConsumed());
    }
}
