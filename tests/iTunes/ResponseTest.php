<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\iTunes\Response;
use ReceiptValidator\iTunes\Transaction;
use ReceiptValidator\iTunes\RenewalInfo;

/**
 * @group      iTunes
 * @coversDefaultClass \ReceiptValidator\iTunes\Response
 */
class ResponseTest extends TestCase
{
    /**
     * Verifies that a modern "iOS 7+" style receipt is parsed correctly.
     *
     * @covers ::__construct
     * @covers ::parse
     * @covers ::getAppItemId
     * @covers ::getBundleId
     * @covers ::getOriginalPurchaseDate
     * @covers ::getRequestDate
     * @covers ::getReceiptCreationDate
     * @covers ::getTransactions
     * @covers ::getLatestReceiptInfo
     * @covers ::getLatestReceipt
     * @covers ::getPendingRenewalInfo
     * @covers ::isRetryable
     */
    public function testIOS7StyleReceiptParsing(): void
    {
        $timestamp = Carbon::now()->getTimestamp() * 1000;
        $data = [
            'receipt' => [
                'app_item_id' => '123456',
                'original_purchase_date_ms' => $timestamp,
                'request_date_ms' => $timestamp,
                'receipt_creation_date_ms' => $timestamp,
                'in_app' => [['transaction_id' => 'tx1']],
                'bundle_id' => 'com.example.test',
            ],
            'latest_receipt_info' => [['transaction_id' => 'tx2']],
            'latest_receipt' => 'base64data',
            'pending_renewal_info' => [['product_id' => 'test_product']],
            'is-retryable' => true,
        ];

        $response = new Response($data, Environment::PRODUCTION);

        $this->assertSame('123456', $response->getAppItemId());
        $this->assertSame('com.example.test', $response->getBundleId());
        $this->assertInstanceOf(Carbon::class, $response->getOriginalPurchaseDate());
        $this->assertInstanceOf(Carbon::class, $response->getRequestDate());
        $this->assertInstanceOf(Carbon::class, $response->getReceiptCreationDate());
        $this->assertCount(1, $response->getTransactions());
        $this->assertInstanceOf(Transaction::class, $response->getTransactions()[0]);
        $this->assertCount(1, $response->getLatestReceiptInfo());
        $this->assertInstanceOf(Transaction::class, $response->getLatestReceiptInfo()[0]);
        $this->assertSame('base64data', $response->getLatestReceipt());
        $this->assertCount(1, $response->getPendingRenewalInfo());
        $this->assertInstanceOf(RenewalInfo::class, $response->getPendingRenewalInfo()[0]);
        $this->assertTrue($response->isRetryable());
    }

    /**
     * Verifies that a legacy "iOS 6" style receipt is parsed correctly.
     *
     * @covers ::__construct
     * @covers ::parse
     */
    public function testIOS6StyleReceiptParsing(): void
    {
        $data = [
            'receipt' => [
                'transaction_id' => 'legacy_tx',
                'product_id' => 'legacy_product',
                'bid' => 'legacy.app',
            ]
        ];

        $response = new Response($data, Environment::PRODUCTION);

        $this->assertSame('legacy.app', $response->getBundleId());
        $this->assertCount(1, $response->getTransactions());
        $this->assertInstanceOf(Transaction::class, $response->getTransactions()[0]);
        $this->assertEquals('legacy_tx', $response->getTransactions()[0]->getTransactionId());
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
        $response = new Response([], Environment::PRODUCTION);
        $this->assertCount(0, $response->getTransactions());
        $this->assertNull($response->getBundleId());
    }
}
