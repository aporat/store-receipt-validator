<?php

namespace ReceiptValidator\Tests\Amazon;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Response;
use ReceiptValidator\Amazon\Transaction;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class ResponseTest extends TestCase
{
    public function testParsesValidResponse(): void
    {
        $receipt = [
            'productId' => 'com.amazon.test',
            'receiptId' => 'txn_001',
            'purchaseDate' => 1609459200000,
            'quantity' => 1
        ];

        $response = new Response($receipt, Environment::PRODUCTION);
        $this->assertSame($receipt, $response->getRawData());

        $purchases = $response->getPurchases();
        $this->assertCount(1, $purchases);
        $this->assertInstanceOf(Transaction::class, $purchases[0]);
    }

    public function testThrowsExceptionOnNullData(): void
    {
        $this->expectException(ValidationException::class);
        new Response(null, Environment::SANDBOX);
    }

    public function testSetAndGetEnvironment(): void
    {
        $receipt = [
            'productId' => 'com.amazon.test',
            'receiptId' => 'txn_002',
            'purchaseDate' => 1609459200000
        ];

        $response = new Response($receipt);
        $this->assertSame(Environment::PRODUCTION, $response->getEnvironment());

        $response->setEnvironment(Environment::SANDBOX);
        $this->assertSame(Environment::SANDBOX, $response->getEnvironment());
    }
}
