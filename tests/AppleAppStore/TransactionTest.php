<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class TransactionTest extends TestCase
{
    public function testParseAndGetters(): void
    {
        $now = Carbon::now()->timestamp * 1000;

        $data = [
            'originalTransactionId' => '1000000000000000',
            'transactionId' => '2000000000000000',
            'webOrderLineItemId' => '3000000000000000',
            'bundleId' => 'com.example.app',
            'productId' => 'com.example.product1',
            'subscriptionGroupIdentifier' => 'group.com.example',
            'quantity' => 1,
            'type' => 'Auto-Renewable Subscription',
            'appAccountToken' => 'abc-123-def-456',
            'inAppOwnershipType' => 'PURCHASED',
            'revocationReason' => '1',
            'isUpgraded' => true,
            'offerType' => 'Intro',
            'offerIdentifier' => 'intro-123',
            'storefront' => 'USA',
            'storefrontId' => '143441',
            'transactionReason' => 'PURCHASE',
            'currency' => 'USD',
            'price' => 999,
            'offerDiscountType' => 'PAY_AS_YOU_GO',
            'appTransactionId' => 'app-transaction-id-1',
            'offerPeriod' => 'P1M',
            'environment' => 'Production',
            'purchaseDate' => $now,
            'originalPurchaseDate' => $now - 86400000,
            'expiresDate' => $now + 86400000,
            'signedDate' => $now + 1000,
            'revocationDate' => $now + 2000,
        ];

        $transaction = new Transaction($data);

        $this->assertSame($data['originalTransactionId'], $transaction->getOriginalTransactionId());
        $this->assertSame($data['transactionId'], $transaction->getTransactionId());
        $this->assertSame($data['webOrderLineItemId'], $transaction->getWebOrderLineItemId());
        $this->assertSame($data['bundleId'], $transaction->getBundleId());
        $this->assertSame($data['productId'], $transaction->getProductId());
        $this->assertSame($data['subscriptionGroupIdentifier'], $transaction->getSubscriptionGroupIdentifier());
        $this->assertSame($data['quantity'], $transaction->getQuantity());
        $this->assertSame($data['type'], $transaction->getType());
        $this->assertSame($data['appAccountToken'], $transaction->getAppAccountToken());
        $this->assertSame($data['inAppOwnershipType'], $transaction->getInAppOwnershipType());
        $this->assertSame($data['revocationReason'], $transaction->getRevocationReason());
        $this->assertSame($data['isUpgraded'], $transaction->getIsUpgraded());
        $this->assertSame($data['offerType'], $transaction->getOfferType());
        $this->assertSame($data['offerIdentifier'], $transaction->getOfferIdentifier());
        $this->assertSame($data['storefront'], $transaction->getStorefront());
        $this->assertSame($data['storefrontId'], $transaction->getStorefrontId());
        $this->assertSame($data['transactionReason'], $transaction->getTransactionReason());
        $this->assertSame($data['currency'], $transaction->getCurrency());
        $this->assertSame($data['price'], $transaction->getPrice());
        $this->assertSame($data['offerDiscountType'], $transaction->getOfferDiscountType());
        $this->assertSame($data['appTransactionId'], $transaction->getAppTransactionId());
        $this->assertSame($data['offerPeriod'], $transaction->getOfferPeriod());
        $this->assertInstanceOf(Carbon::class, $transaction->getPurchaseDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getOriginalPurchaseDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getExpiresDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getSignedDate());
        $this->assertInstanceOf(Carbon::class, $transaction->getRevocationDate());
        $this->assertSame(Environment::PRODUCTION, $transaction->getEnvironment());
        $this->assertSame($data, $transaction->getRawData());

        $transaction['testKey'] = 'testValue';
        $this->assertSame('testValue', $transaction['testKey']);
        $this->assertTrue(isset($transaction['testKey']));
        unset($transaction['testKey']);
        $this->assertFalse(isset($transaction['testKey']));
    }

    public function testInvalidRawDataThrows(): void
    {
        $this->expectException(ValidationException::class);
        new Transaction(null);
    }
}
