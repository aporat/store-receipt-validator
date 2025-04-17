<?php

namespace ReceiptValidator\Tests\iTunes;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\SandboxResponse;
use ReceiptValidator\iTunes\Validator as iTunesValidator;

class ValidatorTest extends TestCase
{
    private string $receiptBase64Data = 'ewoJInNpZ25hdHVyZSIgPSAiQXBNVQ==';

    private iTunesValidator $validator;

    public function testInvalidOptionsToConstructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid endpoint 'in-valid'");
        new iTunesValidator('in-valid');
    }

    public function testSetEndpoint(): void
    {
        $this->validator->setEndpoint(iTunesValidator::ENDPOINT_PRODUCTION);
        $this->assertEquals(iTunesValidator::ENDPOINT_PRODUCTION, $this->validator->getEndpoint());
    }

    public function testSetBase64ReceiptData(): void
    {
        $decoded = base64_decode($this->receiptBase64Data);
        $this->validator->setReceiptData($decoded);
        $this->assertEquals($this->receiptBase64Data, $this->validator->getReceiptData());
    }

    public function testSetReceiptData(): void
    {
        $this->validator->setReceiptData($this->receiptBase64Data);
        $this->assertEquals($this->receiptBase64Data, $this->validator->getReceiptData());
    }

    public function testSetSharedSecret(): void
    {
        $this->validator->setSharedSecret('test-secret');
        $this->assertEquals('test-secret', $this->validator->getSharedSecret());
        $this->validator->setSharedSecret(null);
        $this->assertNull($this->validator->getSharedSecret());
    }

    public function testSetExcludeOldTransactions(): void
    {
        $this->validator->setExcludeOldTransactions(true);
        $this->assertTrue($this->validator->getExcludeOldTransactions());
    }

    public function testSetRequestOptions(): void
    {
        $this->validator->setRequestOptions(['timeout' => 5]);
        $this->assertArrayHasKey('timeout', $this->validator->getRequestOptions());
    }

    public function testValidatorWithValidResponse(): void
    {
        $itunesResponse = [
            "environment" => "Sandbox",
            "latest_receipt" => "MILFMwYJKoZIhvcNAQcCoILFJDCCxSACAQExCzAJBgUrDgMCGgUAMIK05A==",
            "latest_receipt_info" => [
                [
                    "expires_date" => "2014-03-12 10:18:05 Etc/GMT",
                    "expires_date_ms" => 1394619485000,
                    "expires_date_pst" => "2014-03-12 03:18:05 America/Los_Angeles",
                    "is_trial_period" => false,
                    "original_purchase_date" => "2014-03-12 10:15:06 Etc/GMT",
                    "original_purchase_date_ms" => 1394619306000,
                    "original_purchase_date_pst" => "2014-03-12 03:15:06 America/Los_Angeles",
                    "original_transaction_id" => 1000000093384828,
                    "product_id" => "myapp.1",
                    "purchase_date" => "2014-03-25 12:21:23 Etc/GMT",
                    "purchase_date_ms" => 1395750083000,
                    "purchase_date_pst" => "2014-03-25 05:21:23 America/Los_Angeles",
                    "quantity" => 1,
                    "transaction_id" => 1000000104232856,
                    "web_order_line_item_id" => 1000000027948608
                ]
            ],
            "receipt" => [
                "app_item_id" => 11202513425662,
                "bundle_id" => "com.myapp",
                "original_purchase_date_ms" => 1375340400000,
                "receipt_creation_date_ms" => 1375340400000,
                "request_date_ms" => 1432485078143,
                "in_app" => [
                    [
                        "is_trial_period" => false,
                        "original_purchase_date_ms" => 1432429618000,
                        "original_transaction_id" => 1000000156455961,
                        "product_id" => "myapp.1",
                        "purchase_date_ms" => 1432429618000,
                        "quantity" => 1,
                        "transaction_id" => 1000000156455961
                    ]
                ]
            ],
            "status" => 0,
            "pending_renewal_info" => [
                [
                    "auto_renew_product_id" => "Test_Subscription",
                    "original_transaction_id" => "original_transaction_id_value",
                    "product_id" => "Test_Subscription",
                    "auto_renew_status" => "1"
                ]
            ]
        ];

        $mock = new MockHandler([new Response(200, [], json_encode($itunesResponse))]);
        $handler = HandlerStack::create($mock);

        $this->validator->setRequestOptions(['handler' => $handler]);

        $response = $this->validator->setReceiptData($this->receiptBase64Data)->validate();

        $this->assertTrue($response->isValid());
        $this->assertTrue($response->isSandbox());
        $this->assertEquals('com.myapp', $response->getBundleId());
        $this->assertEquals(ResponseInterface::RESULT_OK, $response->getResultCode());
    }

    public function testGetClientConfigSetsBaseUriIfMissing(): void
    {
        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_SANDBOX);
        $validator->setRequestOptions(['timeout' => 10]);
        $config = (new \ReflectionClass($validator))->getMethod('getClientConfig');
        $config->setAccessible(true);
        $result = $config->invoke($validator);

        $this->assertArrayHasKey('base_uri', $result);
        $this->assertEquals(iTunesValidator::ENDPOINT_SANDBOX, $result['base_uri']);
        $this->assertEquals(10, $result['timeout']);
    }

    public function testGetClientCreatesInstanceOnce(): void
    {
        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_SANDBOX);
        $method = (new \ReflectionClass($validator))->getMethod('getClient');
        $method->setAccessible(true);

        $first = $method->invoke($validator);
        $second = $method->invoke($validator);

        $this->assertSame($first, $second);
    }

    public function testPrepareRequestDataIncludesSecret(): void
    {
        $validator = new iTunesValidator();
        $validator->setReceiptData('test-data');
        $validator->setSharedSecret('secret-key');

        $method = (new \ReflectionClass($validator))->getMethod('prepareRequestData');
        $method->setAccessible(true);

        $result = json_decode($method->invoke($validator), true);

        $this->assertEquals('test-data', $result['receipt-data']);
        $this->assertEquals('secret-key', $result['password']);
        $this->assertFalse($result['exclude-old-transactions']);
    }

    public function testValidateRetriesOnSandboxError(): void
    {
        $sandboxBody = ['status' => ResponseInterface::RESULT_OK, 'receipt' => ['app_item_id' => 123, 'in_app' => []]];
        $prodBody = ['status' => ResponseInterface::RESULT_SANDBOX_RECEIPT_SENT_TO_PRODUCTION];

        $mock = new MockHandler([
            new Response(200, [], json_encode($prodBody)),
            new Response(200, [], json_encode($sandboxBody)),
        ]);

        $validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);
        $validator->setReceiptData('fake-data')->setRequestOptions(['handler' => HandlerStack::create($mock)]);
        $response = $validator->validate();

        $this->assertInstanceOf(SandboxResponse::class, $response);
        $this->assertEquals(ResponseInterface::RESULT_OK, $response->getResultCode());
    }

    protected function setUp(): void
    {
        $this->validator = new iTunesValidator(iTunesValidator::ENDPOINT_SANDBOX);
    }
}
