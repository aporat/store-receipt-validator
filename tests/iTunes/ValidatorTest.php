<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\APIError;
use ReceiptValidator\iTunes\Validator;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSetAndGetEnvironment(): void
    {
        $validator = new Validator('secret', Environment::SANDBOX);
        self::assertSame(Environment::SANDBOX, $validator->getEnvironment());

        $validator->setEnvironment(Environment::PRODUCTION);
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());
    }

    public function testSetReceiptData_autoEncodesJson(): void
    {
        $validator = new Validator('secret');
        $json     = '{"example":"json"}';
        $base64   = base64_encode($json);

        $validator->setReceiptData($json);
        self::assertSame($base64, $validator->getReceiptData());

        // If already base64, it should remain unchanged
        $validator->setReceiptData($base64);
        self::assertSame($base64, $validator->getReceiptData());
    }

    public function testGetSharedSecret(): void
    {
        $validator = new Validator('secret');
        self::assertSame('secret', $validator->getSharedSecret());
    }

    public function testSetSharedSecretUpdatesValue(): void
    {
        $validator = new Validator('original');
        $validator->setSharedSecret('updated');
        self::assertSame('updated', $validator->getSharedSecret());
    }

    public function testSetSharedSecretToNull(): void
    {
        $validator = new Validator('secret');
        $validator->setSharedSecret(null);
        self::assertNull($validator->getSharedSecret());
    }

    public function testSetSharedSecretDefaultsToNull(): void
    {
        $validator = new Validator('secret');
        $validator->setSharedSecret();
        self::assertNull($validator->getSharedSecret());
    }

    public function testSetSharedSecretReturnsSelf(): void
    {
        $validator = new Validator('secret');
        $result = $validator->setSharedSecret('new-secret');
        self::assertSame($validator, $result);
    }

    public function testConstructorWithNullSharedSecret(): void
    {
        $validator = new Validator(null);
        self::assertNull($validator->getSharedSecret());
    }

    public function testValidateReturnsResponse(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret');
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('abc');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testRetryOnSandboxError_21007(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        // First call (production) replies with 21007 → retry on SANDBOX
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21007], JSON_THROW_ON_ERROR)));
        // Second call (sandbox) succeeds
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testRetryOnProductionErrorFromSandbox_21008(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        // First call (sandbox) replies with 21008 → retry on PRODUCTION
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21008], JSON_THROW_ON_ERROR)));
        // Second call (production) succeeds
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testThrowsOnInvalidHttpStatus(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(500, [], 'Server error'));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('test');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to get response from iTunes server');

        $validator->validate();
    }

    public function testInAppPurchaseResponseFromFixture(): void
    {
        $json       = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy-data');

        $response = $validator->validate();

        self::assertSame('com.myapp', $response->getBundleId());
        self::assertCount(2, $response->getTransactions());
        self::assertSame('myapp.1', $response->getTransactions()[0]->getProductId());
    }

    public function testInAppPurchaseInvalidReceiptResponseFromFixture(): void
    {
        $json       = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseInvalidReceiptResponse.json');
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $validator = new Validator('secret', Environment::SANDBOX);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy-data');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The data in the receipt-data property was malformed.');

        $validator->validate();
    }

    public function testThrowsValidationExceptionWithFormattedMessage(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21004], JSON_THROW_ON_ERROR)));

        $validator = new Validator('invalid-shared-secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes API error [21004]: The shared secret you provided does not match the shared secret on file for your account.');

        $validator->validate();
    }

    public function testValidateThrowsWhenReceiptDataMissing(): void
    {
        $validator = new Validator('secret', Environment::SANDBOX);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt data must be set before validation.');

        // no setReceiptData() call
        $validator->validate();
    }

    public function testInvalidJsonBodyThrows(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], '{not-json'));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('abc');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server returned invalid JSON');

        $validator->validate();
    }

    public function testSubscriptionExpiredReturnsResponse(): void
    {
        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => APIError::SUBSCRIPTION_EXPIRED->value,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        $validator = new Validator('secret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData('dummy-data');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
        self::assertSame(APIError::SUBSCRIPTION_EXPIRED->value, $response->getRawData()['status']);
    }

    public function testApiErrorFromIntHelper(): void
    {
        self::assertSame(APIError::JSON_INVALID, APIError::fromInt(21000));
        self::assertNull(APIError::fromInt(999999));
    }

    public function testSharedSecretIncludedInRequestPayload(): void
    {
        $capturedRequest = null;

        $mockClient = Mockery::mock(ClientInterface::class);
        $mockClient->shouldReceive('sendRequest')
            ->once()
            ->andReturnUsing(function (RequestInterface $req) use (&$capturedRequest): GuzzleResponse {
                $capturedRequest = $req;
                return new GuzzleResponse(200, [], json_encode([
                    'status'  => 0,
                    'receipt' => ['app_item_id' => 1, 'in_app' => []],
                ], JSON_THROW_ON_ERROR));
            });

        $validator = new Validator('topsecret', Environment::PRODUCTION);
        $validator->setHttpClient($mockClient);
        $validator->setReceiptData(base64_encode('anything'));
        $validator->validate();

        $this->assertNotNull($capturedRequest);
        $this->assertSame('POST', $capturedRequest->getMethod());
        $this->assertStringContainsString('/verifyReceipt', (string) $capturedRequest->getUri());

        $payload = json_decode((string) $capturedRequest->getBody(), true);
        $this->assertIsArray($payload);
        $this->assertSame('topsecret', $payload['password'] ?? null);
        $this->assertArrayHasKey('receipt-data', $payload);

        // Now repeat with NO shared secret → password should be absent
        $capturedRequest2 = null;

        $mockClient2 = Mockery::mock(ClientInterface::class);
        $mockClient2->shouldReceive('sendRequest')
            ->once()
            ->andReturnUsing(function (RequestInterface $req) use (&$capturedRequest2): GuzzleResponse {
                $capturedRequest2 = $req;
                return new GuzzleResponse(200, [], json_encode([
                    'status'  => 0,
                    'receipt' => ['app_item_id' => 1, 'in_app' => []],
                ], JSON_THROW_ON_ERROR));
            });

        $validator2 = new Validator(null, Environment::PRODUCTION);
        $validator2->setHttpClient($mockClient2);
        $validator2->setReceiptData(base64_encode('anything'));
        $validator2->validate();

        $this->assertNotNull($capturedRequest2);
        $payload2 = json_decode((string) $capturedRequest2->getBody(), true);
        $this->assertIsArray($payload2);
        $this->assertArrayNotHasKey('password', $payload2);
        $this->assertArrayHasKey('receipt-data', $payload2);
    }
}
