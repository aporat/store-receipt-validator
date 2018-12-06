<?php

use ReceiptValidator\iTunes\ProductionResponse;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\SandboxResponse;
use PHPUnit\Framework\TestCase;

/**
 * @group library
 */
class iTunesResponseTest extends TestCase
{

    public function testInvalidOptionsToConstructor(): void
    {
        $this->expectException(\ReceiptValidator\RunTimeException::class);

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
        $jsonResponseString = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
        $jsonResponseArray = json_decode($jsonResponseString, true);

        $response = new ProductionResponse($jsonResponseArray);

        $this->assertEquals(
            ResponseInterface::RESULT_OK,
            $response->getResultCode()
        );

        $this->assertContainsOnlyInstancesOf(
            ReceiptValidator\iTunes\PurchaseItem::class,
            $response->getLatestReceiptInfo()
        );

        $this->assertCount(
            2,
            $response->getLatestReceiptInfo()
        );

        $this->assertEquals(
            $jsonResponseArray['latest_receipt'],
            $response->getLatestReceipt(),
            'latest receipt must match'
        );

        $this->assertEquals(
            $jsonResponseArray['receipt']['bundle_id'],
            $response->getBundleId(),
            'receipt bundle id must match'
        );

        $this->assertEquals(
            '2013-08-01T07:00:00+00:00',
            $response->getOriginalPurchaseDate()->toIso8601String()
        );

        $this->assertEquals(
            '2013-08-01T07:00:00+00:00',
            $response->getReceiptCreationDate()->toIso8601String()
        );

        $this->assertEquals(
            '2015-05-24T16:31:18+00:00',
            $response->getRequestDate()->toIso8601String()
        );

        $this->assertContainsOnlyInstancesOf(
            ReceiptValidator\iTunes\PendingRenewalInfo::class,
            $response->getPendingRenewalInfo()
        );

        $this->assertEquals(
            11202513425662,
            $response->getAppItemId()
        );

        $this->assertEquals(
            1000000093384828,
            $response->getLatestReceiptInfo()[0]->getTransactionId()
        );

        $this->assertEquals(
            $jsonResponseArray,
            $response->getRawData()
        );

        $this->assertEquals(
            false,
            $response->isRetryable()
        );
    }
}
