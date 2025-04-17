<?php

namespace ReceiptValidator\Tests\iTunes;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\PendingRenewalInfo;
use ReceiptValidator\iTunes\ProductionResponse;
use ReceiptValidator\iTunes\PurchaseItem;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\SandboxResponse;
use ReceiptValidator\RunTimeException;

class ResponseTest extends TestCase
{
    public function testInvalidOptionsToConstructor(): void
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage('Response must be an array');

        new ProductionResponse(null);
    }

    public function testInvalidReceipt(): void
    {
        $response = new ProductionResponse(['status' => ResponseInterface::RESULT_DATA_MALFORMED, 'receipt' => []]);

        $this->assertFalse(
            $response->isValid(),
            'receipt must be invalid'
        );

        $this->assertEquals(
            ResponseInterface::RESULT_DATA_MALFORMED,
            $response->getResultCode(),
            'receipt result code must match'
        );
    }

    public function testReceiptSentToWrongEndpoint(): void
    {
        $response = new SandboxResponse(
            [
                'status' => ResponseInterface::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION,
            ]
        );

        $this->assertFalse(
            $response->isValid(),
            'receipt must be invalid'
        );

        $this->assertEquals(
            ResponseInterface::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION,
            $response->getResultCode(),
            'receipt result code must match'
        );
    }

    public function testResponseWithStatusExpiredReceiptIsValid(): void
    {
        $response = new ProductionResponse([
            'status' => ResponseInterface::RESULT_RECEIPT_VALID_BUT_SUB_EXPIRED,
            'receipt' => [],
        ]);

        $this->assertTrue($response->isValid());
    }

    public function testResponseEnvironment(): void
    {
        $this->assertTrue((new ProductionResponse([]))->isProduction());
        $this->assertFalse((new ProductionResponse([]))->isSandbox());
        $this->assertTrue((new SandboxResponse([]))->isSandbox());
        $this->assertFalse((new SandboxResponse([]))->isProduction());
    }

    public function testResponseMightHasNullableEmptyReceipt(): void
    {
        $this->assertNull((new ProductionResponse([]))->getLatestReceipt());
    }

    public function testValidReceipt(): void
    {
        $response = new ProductionResponse(
            ['status' => ResponseInterface::RESULT_OK, 'receipt' => ['testValue']]
        );

        $this->assertTrue($response->isValid(), 'receipt must be valid');
        $this->assertEquals(ResponseInterface::RESULT_OK, $response->getResultCode(), 'receipt result code must match');
    }

    public function testReceiptWithLatestReceiptInfo(): void
    {
        $response = new ProductionResponse([
            'status' => ResponseInterface::RESULT_OK,
            'environment' => 'Sandbox',
            'latest_receipt' => 'MILFMwYJKoZIhvcNAQcCoILFJDCCxSACAQExCzAJBgUrDgMCGgUAMIK05A==',
            'latest_receipt_info' => [
                [
                    'expires_date' => '2014-03-12 10:18:05 Etc/GMT',
                    'expires_date_ms' => 1394619485000,
                    'expires_date_pst' => '2014-03-12 03:18:05 America/Los_Angeles',
                    'is_trial_period' => false,
                    'original_purchase_date' => '2014-03-12 10:15:06 Etc/GMT',
                    'original_purchase_date_ms' => 1394619306000,
                    'original_purchase_date_pst' => '2014-03-12 03:15:06 America/Los_Angeles',
                    'original_transaction_id' => 1000000093384828,
                    'product_id' => 'myapp.1',
                    'purchase_date' => '2014-03-25 12:21:23 Etc/GMT',
                    'purchase_date_ms' => 1395750083000,
                    'purchase_date_pst' => '2014-03-25 05:21:23 America/Los_Angeles',
                    'quantity' => 1,
                    'transaction_id' => 1000000104232856,
                    'web_order_line_item_id' => 1000000027948608
                ]
            ],
            'receipt' => [
                'app_item_id' => 11202513425662,
                'bundle_id' => 'com.myapp',
                'original_purchase_date_ms' => 1375340400000,
                'receipt_creation_date_ms' => 1375340400000,
                'request_date_ms' => 1432485078143,
                'in_app' => [
                    [
                        'is_trial_period' => false,
                        'original_purchase_date_ms' => 1432429618000,
                        'original_transaction_id' => 1000000156455961,
                        'product_id' => 'myapp.1',
                        'purchase_date_ms' => 1432429618000,
                        'quantity' => 1,
                        'transaction_id' => 1000000156455961
                    ]
                ]
            ],
            'pending_renewal_info' => [
                [
                    'auto_renew_product_id' => 'Test_Subscription',
                    'original_transaction_id' => 'original_transaction_id_value',
                    'product_id' => 'Test_Subscription',
                    'auto_renew_status' => '1'
                ]
            ]
        ]);

        $this->assertTrue($response->isValid());
        $this->assertCount(1, $response->getLatestReceiptInfo());
        $this->assertEquals('myapp.1', $response->getLatestReceiptInfo()[0]->getProductId());
        $this->assertEquals('com.myapp', $response->getBundleId());
    }
}
