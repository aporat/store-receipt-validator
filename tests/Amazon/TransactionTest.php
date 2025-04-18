<?php

namespace ReceiptValidator\Tests\Amazon;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Transaction;
use ReceiptValidator\Exceptions\ValidationException;

class TransactionTest extends TestCase
{
    public function testValidPurchaseItem(): void
    {
        $data = [
            'productId' => 'com.amazon.sample',
            'receiptId' => 'txn123',
            'quantity' => 1,
            'purchaseDate' => 1609459200000,
            'cancelDate' => 1612137600000,
            'renewalDate' => 1614748800000,
            'GracePeriodEndDate' => 1614840000000,
            'freeTrialEndDate' => 1614930000000,
            'AutoRenewing' => true,
            'term' => '1 Month',
            'termSku' => 'sub1-monthly',
        ];

        $item = new Transaction($data);

        $this->assertEquals(1, $item->getQuantity());
        $this->assertEquals('com.amazon.sample', $item->getProductId());
        $this->assertEquals('txn123', $item->getTransactionId());
        $this->assertEquals(Carbon::createFromTimestampUTC(1609459200), $item->getPurchaseDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1612137600), $item->getCancellationDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1614748800), $item->getRenewalDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1614840000), $item->getGracePeriodEndDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1614930000), $item->getFreeTrialEndDate());
        $this->assertTrue($item->isAutoRenewing());
        $this->assertEquals('1 Month', $item->getTerm());
        $this->assertEquals('sub1-monthly', $item->getTermSku());
    }

    public function testOffsetAccess(): void
    {
        $data = ['productId' => 'com.amazon.sample', 'receiptId' => 'txn123', 'quantity' => 2];
        $item = new Transaction($data);

        $this->assertTrue(isset($item['productId']));
        $this->assertEquals('com.amazon.sample', $item['productId']);

        $item['quantity'] = 5;
        $this->assertEquals(5, $item->getQuantity());

        unset($item['quantity']);
        $this->assertFalse(isset($item['quantity']));
    }

    public function testThrowsOnInvalidData(): void
    {
        $this->expectException(ValidationException::class);
        new Transaction(null);
    }
}
