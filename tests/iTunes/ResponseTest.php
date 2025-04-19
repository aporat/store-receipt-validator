<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Response;

class ResponseTest extends TestCase
{
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
        $this->assertCount(1, $response->getLatestReceiptInfo());
        $this->assertSame('base64data', $response->getLatestReceipt());
        $this->assertCount(1, $response->getPendingRenewalInfo());
        $this->assertTrue($response->isRetryable());
    }

    public function testIOS6StyleReceiptParsing(): void
    {
        $data = [
            'receipt' => [
                'transaction_id' => 'legacy_tx',
                'bid' => 'legacy.app',
            ]
        ];

        $response = new Response($data, Environment::PRODUCTION);

        $this->assertSame('legacy.app', $response->getBundleId());
        $this->assertCount(1, $response->getTransactions());
    }

    public function testInvalidReceiptThrows(): void
    {
        $this->expectException(ValidationException::class);
        new Response(null, Environment::PRODUCTION);
    }
}
