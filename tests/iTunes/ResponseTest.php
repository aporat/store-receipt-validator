<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Response;

class ResponseTest extends TestCase
{
    public function testHandlesIOS6StyleReceipt(): void
    {
        $data = [
            'status' => 0,
            'receipt' => [
                'bid' => 'com.example.app',
                'product_id' => 'test.product',
                'purchase_date_ms' => 1600000000000,
                'quantity' => 1,
                'transaction_id' => 'txn1'
            ]
        ];

        $response = new Response($data, Environment::SANDBOX);

        $this->assertEquals('com.example.app', $response->getBundleId());
        $this->assertCount(1, $response->getTransactions());
    }

    public function testHandlesMissingOptionalFields(): void
    {
        $data = [
            'status' => 0,
            'receipt' => [
                'app_item_id' => '456',
                'in_app' => [],
            ]
        ];

        $response = new Response($data);

        $this->assertEquals([], $response->getTransactions());
        $this->assertEquals([], $response->getLatestReceiptInfo());
        $this->assertEquals([], $response->getPendingRenewalInfo());
    }

    public function testDateParsing(): void
    {
        $ms = 1600000000000;
        $data = [
            'status' => 0,
            'receipt' => [
                'app_item_id' => '456',
                'original_purchase_date_ms' => $ms,
                'receipt_creation_date_ms' => $ms,
                'request_date_ms' => $ms,
                'in_app' => []
            ]
        ];

        $response = new Response($data);
        $expected = Carbon::createFromTimestampUTC($ms / 1000);

        $this->assertEquals($expected, $response->getOriginalPurchaseDate());
        $this->assertEquals($expected, $response->getReceiptCreationDate());
        $this->assertEquals($expected, $response->getRequestDate());
    }

    public function testRetryableFlag(): void
    {
        $data = [
            'status' => 0,
            'is-retryable' => true,
            'receipt' => [
                'app_item_id' => '456',
                'in_app' => []
            ]
        ];

        $response = new Response($data);
        $this->assertTrue($response->isRetryable());
    }

    public function testThrowsExceptionOnInvalidData(): void
    {
        $this->expectException(ValidationException::class);
        /** @phpstan-ignore-next-line */
        new Response(null);
    }
}
