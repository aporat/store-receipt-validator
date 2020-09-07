<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\ResponseInterface;
use ReceiptValidator\iTunes\Validator as iTunesValidator;

/**
 * @group library
 */
class iTunesValidatorTest extends TestCase
{
    private $receiptBase64Data = 'ewoJInNpZ25hdHVyZSIgPSAiQXBNVUJDODZBbHpOaWtWNVl0clpBTWlKUWJLOEVkZVhrNjNrV0JBWHpsQzhkWEd1anE0N1puSVlLb0ZFMW9OL0ZTOGNYbEZmcDlZWHQ5aU1CZEwyNTBsUlJtaU5HYnloaXRyeVlWQVFvcmkzMlc5YVIwVDhML2FZVkJkZlcrT3kvUXlQWkVtb05LeGhudDJXTlNVRG9VaFo4Wis0cFA3MHBlNWtVUWxiZElWaEFBQURWekNDQTFNd2dnSTdvQU1DQVFJQ0NHVVVrVTNaV0FTMU1BMEdDU3FHU0liM0RRRUJCUVVBTUg4eEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURXpNREVHQTFVRUF3d3FRWEJ3YkdVZ2FWUjFibVZ6SUZOMGIzSmxJRU5sY25ScFptbGpZWFJwYjI0Z1FYVjBhRzl5YVhSNU1CNFhEVEE1TURZeE5USXlNRFUxTmxvWERURTBNRFl4TkRJeU1EVTFObG93WkRFak1DRUdBMVVFQXd3YVVIVnlZMmhoYzJWU1pXTmxhWEIwUTJWeWRHbG1hV05oZEdVeEd6QVpCZ05WQkFzTUVrRndjR3hsSUdsVWRXNWxjeUJUZEc5eVpURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd2daOHdEUVlKS29aSWh2Y05BUUVCQlFBRGdZMEFNSUdKQW9HQkFNclJqRjJjdDRJclNkaVRDaGFJMGc4cHd2L2NtSHM4cC9Sd1YvcnQvOTFYS1ZoTmw0WElCaW1LalFRTmZnSHNEczZ5anUrK0RyS0pFN3VLc3BoTWRkS1lmRkU1ckdYc0FkQkVqQndSSXhleFRldngzSExFRkdBdDFtb0t4NTA5ZGh4dGlJZERnSnYyWWFWczQ5QjB1SnZOZHk2U01xTk5MSHNETHpEUzlvWkhBZ01CQUFHamNqQndNQXdHQTFVZEV3RUIvd1FDTUFBd0h3WURWUjBqQkJnd0ZvQVVOaDNvNHAyQzBnRVl0VEpyRHRkREM1RllRem93RGdZRFZSMFBBUUgvQkFRREFnZUFNQjBHQTFVZERnUVdCQlNwZzRQeUdVakZQaEpYQ0JUTXphTittVjhrOVRBUUJnb3Foa2lHOTJOa0JnVUJCQUlGQURBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQUVhU2JQanRtTjRDL0lCM1FFcEszMlJ4YWNDRFhkVlhBZVZSZVM1RmFaeGMrdDg4cFFQOTNCaUF4dmRXLzNlVFNNR1k1RmJlQVlMM2V0cVA1Z204d3JGb2pYMGlreVZSU3RRKy9BUTBLRWp0cUIwN2tMczlRVWU4Y3pSOFVHZmRNMUV1bVYvVWd2RGQ0TndOWXhMUU1nNFdUUWZna1FRVnk4R1had1ZIZ2JFL1VDNlk3MDUzcEdYQms1MU5QTTN3b3hoZDNnU1JMdlhqK2xvSHNTdGNURXFlOXBCRHBtRzUrc2s0dHcrR0szR01lRU41LytlMVFUOW5wL0tsMW5qK2FCdzdDMHhzeTBiRm5hQWQxY1NTNnhkb3J5L0NVdk02Z3RLc21uT09kcVRlc2JwMGJzOHNuNldxczBDOWRnY3hSSHVPTVoydG04bnBMVW03YXJnT1N6UT09IjsKCSJwdXJjaGFzZS1pbmZvIiA9ICJld29KSW05eWFXZHBibUZzTFhCMWNtTm9ZWE5sTFdSaGRHVXRjSE4wSWlBOUlDSXlNREV5TFRBMExUTXdJREE0T2pBMU9qVTFJRUZ0WlhKcFkyRXZURzl6WDBGdVoyVnNaWE1pT3dvSkltOXlhV2RwYm1Gc0xYUnlZVzV6WVdOMGFXOXVMV2xrSWlBOUlDSXhNREF3TURBd01EUTJNVGM0T0RFM0lqc0tDU0ppZG5KeklpQTlJQ0l5TURFeU1EUXlOeUk3Q2draWRISmhibk5oWTNScGIyNHRhV1FpSUQwZ0lqRXdNREF3TURBd05EWXhOemc0TVRjaU93b0pJbkYxWVc1MGFYUjVJaUE5SUNJeElqc0tDU0p2Y21sbmFXNWhiQzF3ZFhKamFHRnpaUzFrWVhSbExXMXpJaUE5SUNJeE16TTFOems0TXpVMU9EWTRJanNLQ1NKd2NtOWtkV04wTFdsa0lpQTlJQ0pqYjIwdWJXbHVaRzF2WW1Gd2NDNWtiM2R1Ykc5aFpDSTdDZ2tpYVhSbGJTMXBaQ0lnUFNBaU5USXhNVEk1T0RFeUlqc0tDU0ppYVdRaUlEMGdJbU52YlM1dGFXNWtiVzlpWVhCd0xrMXBibVJOYjJJaU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFek16VTNPVGd6TlRVNE5qZ2lPd29KSW5CMWNtTm9ZWE5sTFdSaGRHVWlJRDBnSWpJd01USXRNRFF0TXpBZ01UVTZNRFU2TlRVZ1JYUmpMMGROVkNJN0Nna2ljSFZ5WTJoaGMyVXRaR0YwWlMxd2MzUWlJRDBnSWpJd01USXRNRFF0TXpBZ01EZzZNRFU2TlRVZ1FXMWxjbWxqWVM5TWIzTmZRVzVuWld4bGN5STdDZ2tpYjNKcFoybHVZV3d0Y0hWeVkyaGhjMlV0WkdGMFpTSWdQU0FpTWpBeE1pMHdOQzB6TUNBeE5Ub3dOVG8xTlNCRmRHTXZSMDFVSWpzS2ZRPT0iOwoJImVudmlyb25tZW50IiA9ICJTYW5kYm94IjsKCSJwb2QiID0gIjEwMCI7Cgkic2lnbmluZy1zdGF0dXMiID0gIjAiOwp9';

    /**
     * @var iTunesValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new iTunesValidator(iTunesValidator::ENDPOINT_SANDBOX);
    }

    public function testInvalidOptionsToConstructor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid endpoint 'in-valid'");

        new iTunesValidator('in-valid');
    }

    public function testSetEndpoint(): void
    {
        $this->validator->setEndpoint(iTunesValidator::ENDPOINT_PRODUCTION);

        $this->assertEquals(
            iTunesValidator::ENDPOINT_PRODUCTION,
            $this->validator->getEndpoint()
        );
    }

    public function testSetBase64ReceiptData(): void
    {
        $receiptBase64Data = base64_decode($this->receiptBase64Data);
        $this->validator->setReceiptData($receiptBase64Data);

        $this->assertEquals(
            $this->receiptBase64Data,
            $this->validator->getReceiptData()
        );
    }

    public function testSetReceiptData(): void
    {
        $this->validator->setReceiptData($this->receiptBase64Data);

        $this->assertEquals(
            $this->receiptBase64Data,
            $this->validator->getReceiptData()
        );
    }

    public function testSetSharedSecret(): void
    {
        $this->validator->setSharedSecret('test-shared-secret');
        $this->assertEquals(
            'test-shared-secret',
            $this->validator->getSharedSecret()
        );

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
        $this->validator->setRequestOptions(['timeout' => 10]);
        $this->assertArrayHasKey('timeout', $this->validator->getRequestOptions());
    }

    public function testValidatorWithValidResponse(): void
    {
        $json_response = file_get_contents(__DIR__.'/fixtures/inAppPurchaseResponse.json');

        $mock = new MockHandler([
            new Response(200, [], $json_response),
        ]);

        $handler = HandlerStack::create($mock);

        $this->validator->setRequestOptions(['handler' => $handler]);

        $response = $this->validator->setReceiptData($this->receiptBase64Data)->validate();
        $this->assertTrue($response->isValid());
        $this->assertEquals(ResponseInterface::RESULT_OK, $response->getResultCode());
        $this->assertCount(2, $response->getPurchases());

        $this->assertTrue($response->isSandbox());
        $this->assertEquals('com.myapp', $response->getBundleId());
        $this->assertEquals(Carbon::parse('2013-08-01 07:00:00 Etc/GMT'), $response->getOriginalPurchaseDate());

        $first_purchase = $response->getPurchases()[0];
        $this->assertEquals('myapp.1', $first_purchase->getProductId());
        $this->assertEquals('1000000156455961', $first_purchase->getTransactionId());

        $pending_renewal_info = $response->getPendingRenewalInfo()[0];
        $this->assertEquals('Test_Subscription', $pending_renewal_info->getProductId());
        $this->assertEquals('original_transaction_id_value', $pending_renewal_info->getOriginalTransactionId());
    }

    public function testValidatorWithInvalidResponse(): void
    {
        $json_response = file_get_contents(__DIR__.'/fixtures/inAppPurchaseInvalidReceiptResponse.json');

        $mock = new MockHandler([
            new Response(200, [], $json_response),
        ]);

        $handler = HandlerStack::create($mock);

        $this->validator->setRequestOptions(['handler' => $handler]);

        $response = $this->validator->setReceiptData($this->receiptBase64Data)->validate();
        $this->assertFalse($response->isValid());
        $this->assertEquals(ResponseInterface::RESULT_DATA_MALFORMED, $response->getResultCode());
    }
}
