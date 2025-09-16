<?php

namespace ReceiptValidator\Tests\Amazon;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Transaction;

/**
 * @group      amazon
 * @coversDefaultClass \ReceiptValidator\Amazon\Transaction
 */
class TransactionTest extends TestCase
{
    /**
     * Verifies that all getters return the correct data from a valid, fully populated
     * transaction payload.
     *
     * @covers ::__construct
     */
    public function testParsesFullyPopulatedData(): void
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

    /**
     * Verifies that creating a transaction with an empty data array is a valid
     * state and populates properties with their expected default values.
     *
     * @covers ::__construct
     */
    public function testEmptyDataIsValid(): void
    {
        $item = new Transaction([]);

        $this->assertNull($item->getProductId());
        $this->assertNull($item->getTransactionId());
        $this->assertEquals(1, $item->getQuantity(), 'Quantity should default to 1 for Amazon transactions');
    }
}
