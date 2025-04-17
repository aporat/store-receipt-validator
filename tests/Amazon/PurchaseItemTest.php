<?php

namespace ReceiptValidator\Tests\Amazon;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\PurchaseItem;
use Carbon\Carbon;
use TypeError;

class PurchaseItemTest extends TestCase
{
    private array $validResponse;

    protected function setUp(): void
    {
        $this->validResponse = [
            'quantity' => 3,
            'receiptId' => 'txn_001',
            'productId' => 'prod_001',
            'purchaseDate' => 1713350400000,   // Corresponds to 2024-04-17
            'cancelDate' => 1714608000000,     // Corresponds to 2024-05-02
            'renewalDate' => 1717286400000     // Corresponds to 2024-06-02
        ];
    }

    public function testConstructorAndParsing(): void
    {
        $item = new PurchaseItem($this->validResponse);

        $this->assertEquals(3, $item->getQuantity());
        $this->assertEquals('txn_001', $item->getTransactionId());
        $this->assertEquals('prod_001', $item->getProductId());
        $this->assertEquals(Carbon::createFromTimestampUTC(1713350400), $item->getPurchaseDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1714608000), $item->getCancellationDate());
        $this->assertEquals(Carbon::createFromTimestampUTC(1717286400), $item->getRenewalDate());
    }

    public function testConstructorWithNull(): void
    {
        $item = new PurchaseItem(null);
        $this->assertNull($item->getRawResponse());
    }

    public function testInvalidJsonResponseThrowsException(): void
    {
        $this->expectException(TypeError::class);
        new PurchaseItem('invalid_response');
    }

}
