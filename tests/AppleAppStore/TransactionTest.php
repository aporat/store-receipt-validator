<?php

namespace ReceiptValidator\AppleAppStore\Tests;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Exceptions\ValidationException;

class TransactionTest extends TestCase
{
    public function testParseWithValidData(): void
    {
        $now = Carbon::now()->getTimestampMs();

        $data = [
            'originalTransactionId' => '1000000709876543',
            'transactionId' => '1000000709876544',
            'webOrderLineItemId' => '2000000000000000',
            'bundleId' => 'com.example.app',
            'productId' => 'com.example.product1',
            'subscriptionGroupIdentifier' => '12345678',
            'quantity' => 1,
            'type' => 'Auto-Renewable Subscription',
            'appAccountToken' => 'token123',
            'inAppOwnershipType' => 'PURCHASED',
            'revocationReason' => '1',
            'isUpgraded' => true,
            'offerType' => 'PROMO',
            'offerIdentifier' => 'PROMO2023',
            'storefront' => 'USA',
            'storefrontId' => '143441',
            'transactionReason' => 'PURCHASE',
            'currency' => 'USD',
            'price' => 999,
            'offerDiscountType' => 'INTRODUCTORY',
            'appTransactionId' => 'appTx123',
            'offerPeriod' => 'P1M',
            'purchaseDate' => $now,
            'originalPurchaseDate' => $now,
            'expiresDate' => $now,
            'signedDate' => $now,
            'revocationDate' => $now,
            'environment' => 'Production',
        ];

        $transaction = new Transaction($data);

        $this->assertEquals('1000000709876543', $transaction->getOriginalTransactionId());
        $this->assertEquals('1000000709876544', $transaction->getTransactionId());
        $this->assertEquals('com.example.product1', $transaction->getProductId());
        $this->assertEquals(999, $transaction->getPrice());
        $this->assertInstanceOf(Carbon::class, $transaction->getPurchaseDate());
        $this->assertTrue($transaction->getIsUpgraded());
    }

    public function testParseWithInvalidDataThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        new Transaction(null);
    }

    public function testOffsetAccess(): void
    {
        $data = [
            'transactionId' => 'test123',
            'environment' => 'Sandbox',
        ];

        $transaction = new Transaction($data);

        $this->assertEquals('test123', $transaction['transactionId']);
        $this->assertTrue(isset($transaction['transactionId']));

        unset($transaction['transactionId']);
        $this->assertFalse(isset($transaction['transactionId']));
    }
}
