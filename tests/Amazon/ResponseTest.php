<?php

namespace ReceiptValidator\Tests\Amazon;

use DateTimeImmutable;
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
     * @covers ::getReceiptId
     * @covers ::getProductId
     * @covers ::getUserId
     * @covers ::getProductType
     * @covers ::getPurchaseDate
     * @covers ::getCancelDate
     * @covers ::isTestTransaction
     */
    public function testParsesValidResponse(): void
    {
        $receipt = [
            'receiptId' => 'txn_001',
            'productId' => 'com.amazon.test.product',
            'userId' => 'amzn1.account.testuser',
            'productType' => 'CONSUMABLE',
            'purchaseDate' => 1609459200000,
            'testTransaction' => true,
        ];

        $response = new Response($receipt, Environment::PRODUCTION);

        $this->assertSame($receipt, $response->getRawData());
        $this->assertSame(Environment::PRODUCTION, $response->getEnvironment());

        $transactions = $response->getTransactions();
        $this->assertCount(1, $transactions);
        $this->assertInstanceOf(Transaction::class, $transactions[0]);

        $this->assertSame('txn_001', $response->getReceiptId());
        $this->assertSame('com.amazon.test.product', $response->getProductId());
        $this->assertSame('amzn1.account.testuser', $response->getUserId());
        $this->assertSame('CONSUMABLE', $response->getProductType());
        $this->assertTrue($response->isTestTransaction());

        $expectedPurchaseDate = (new DateTimeImmutable())->setTimestamp(1609459200);
        $this->assertEquals($expectedPurchaseDate, $response->getPurchaseDate());
        $this->assertNull($response->getCancellationDate());
    }

    public function testParsesResponseWithCancelDate(): void
    {
        $receipt = [
            'receiptId' => 'txn_002',
            'productId' => 'com.amazon.test.subscription',
            'purchaseDate' => 1609459200000,
            'cancelDate' => 1612137600000,
            'productType' => 'SUBSCRIPTION',
            'testTransaction' => false,
        ];

        $response = new Response($receipt);

        $this->assertSame('txn_002', $response->getReceiptId());
        $this->assertSame('com.amazon.test.subscription', $response->getProductId());
        $this->assertFalse($response->isTestTransaction());

        $expectedPurchaseDate = (new DateTimeImmutable())->setTimestamp(1609459200);
        $expectedCancelDate = (new DateTimeImmutable())->setTimestamp(1612137600);

        $this->assertEquals($expectedPurchaseDate, $response->getPurchaseDate());
        $this->assertEquals($expectedCancelDate, $response->getCancellationDate());
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
        $response = new Response([], Environment::SANDBOX);
        $this->assertCount(0, $response->getTransactions());
        $this->assertNull($response->getReceiptId());
        $this->assertNull($response->getProductId());
        $this->assertNull($response->getUserId());
        $this->assertNull($response->getProductType());
        $this->assertNull($response->getPurchaseDate());
        $this->assertNull($response->getCancellationDate());
        $this->assertFalse($response->isTestTransaction());
    }
}
