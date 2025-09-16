<?php

namespace ReceiptValidator\Tests\Amazon;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Response;
use ReceiptValidator\Amazon\Transaction;
use ReceiptValidator\Environment;

/**
 * @group      amazon
 * @coversDefaultClass \ReceiptValidator\Amazon\Response
 */
class ResponseTest extends TestCase
{
    /**
     * Verifies that the constructor correctly parses a valid data array and
     * that all getters return the expected values.
     *
     * @covers ::__construct
     * @covers ::parse
     * @covers ::getRawData
     * @covers ::getEnvironment
     * @covers ::getTransactions
     */
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
        $this->assertSame(Environment::PRODUCTION, $response->getEnvironment());

        $purchases = $response->getTransactions();
        $this->assertCount(1, $purchases);
        $this->assertInstanceOf(Transaction::class, $purchases[0]);
    }

    /**
     * Verifies that creating a response with an empty data array is a valid
     * state and does not throw an exception.
     *
     * @covers ::__construct
     * @covers ::parse
     */
    public function testEmptyResponseIsValid(): void
    {
        // An empty array should create a valid, empty response object without throwing an exception.
        $response = new Response([], Environment::SANDBOX);
        $this->assertCount(0, $response->getTransactions());
        $this->assertNull($response->getReceiptId());
    }
}
