<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
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

    public function testValidateReturnsResponse(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret'])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('abc');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testRetryOnSandboxError_21007(): void
    {
        $mockClient = Mockery::mock(Client::class);
        // First call (production) replies with 21007 → retry on SANDBOX
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21007], JSON_THROW_ON_ERROR)));
        // Second call (sandbox) succeeds
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testRetryOnProductionErrorFromSandbox_21008(): void
    {
        $mockClient = Mockery::mock(Client::class);
        // First call (sandbox) replies with 21008 → retry on PRODUCTION
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21008], JSON_THROW_ON_ERROR)));
        // Second call (production) succeeds
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('xyz');

        $response = $validator->validate();
        self::assertIsArray($response->getRawData());
    }

    public function testThrowsOnInvalidHttpStatus(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(500, [], 'Server error'));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('test');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to get response from iTunes server');

        $validator->validate();
    }

    public function testInAppPurchaseResponseFromFixture(): void
    {
        $json       = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseResponse.json');
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('dummy-data');

        $response = $validator->validate();

        self::assertSame('com.myapp', $response->getBundleId());
        self::assertCount(2, $response->getTransactions());
        self::assertSame('myapp.1', $response->getTransactions()[0]->getProductId());
    }

    public function testInAppPurchaseInvalidReceiptResponseFromFixture(): void
    {
        $json       = file_get_contents(__DIR__ . '/fixtures/inAppPurchaseInvalidReceiptResponse.json');
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('dummy-data');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The data in the receipt-data property was malformed.');

        $validator->validate();
    }

    public function testThrowsValidationExceptionWithFormattedMessage(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode(['status' => 21004], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['invalid-shared-secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('dummy');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes API error [21004]: The shared secret you provided does not match the shared secret on file for your account.');

        $validator->validate();
    }

    public function testValidateThrowsWhenReceiptDataMissing(): void
    {
        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::SANDBOX])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Receipt data must be set before validation.');

        // no setReceiptData() call
        $validator->validate();
    }

    public function testInvalidJsonBodyThrows(): void
    {
        $mockClient = Mockery::mock(Client::class);
        // Return invalid JSON to exercise JSON error handling
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], '{not-json'));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        $validator->setReceiptData('abc');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('iTunes server returned invalid JSON');

        $validator->validate();
    }

    public function testSubscriptionExpiredReturnsResponse(): void
    {
        // Apple returns 21006 when the subscription is expired but the receipt is otherwise valid.
        // Library should not throw; it should return a Response so callers can inspect it.
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => APIError::SUBSCRIPTION_EXPIRED->value,
                'receipt' => ['app_item_id' => 123, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['secret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

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
        // We’ll assert the request body contains "password" only when a shared secret is set
        $captured = [];

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri, array $options) use (&$captured): bool {
                $captured['first'] = $options;
                return $method === 'POST' && $uri === '/verifyReceipt';
            })
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 1, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator */
        $validator = Mockery::mock(Validator::class, ['topsecret', Environment::PRODUCTION])->makePartial();
        $validator->shouldAllowMockingProtectedMethods();
        $validator->shouldReceive('getClient')->andReturn($mockClient);

        // Base64 payload path (not raw JSON)
        $validator->setReceiptData(base64_encode('anything'));
        $validator->validate();

        $this->assertArrayHasKey('body', $captured['first']);
        $payload = json_decode($captured['first']['body'] ?? '{}', true);
        $this->assertIsArray($payload);
        $this->assertSame('topsecret', $payload['password'] ?? null);
        $this->assertArrayHasKey('receipt-data', $payload);

        // Now repeat with NO shared secret → password should be absent
        $captured = [];

        $mockClient2 = Mockery::mock(Client::class);
        $mockClient2->shouldReceive('request')
            ->once()
            ->withArgs(function (string $method, string $uri, array $options) use (&$captured): bool {
                $captured['second'] = $options;
                return $method === 'POST' && $uri === '/verifyReceipt';
            })
            ->andReturn(new GuzzleResponse(200, [], json_encode([
                'status'  => 0,
                'receipt' => ['app_item_id' => 1, 'in_app' => []],
            ], JSON_THROW_ON_ERROR)));

        /** @var Validator|MockInterface $validator2 */
        $validator2 = Mockery::mock(Validator::class, [null, Environment::PRODUCTION])->makePartial();
        $validator2->shouldAllowMockingProtectedMethods();
        $validator2->shouldReceive('getClient')->andReturn($mockClient2);

        $validator2->setReceiptData(base64_encode('anything'));
        $validator2->validate();

        $payload2 = json_decode($captured['second']['body'] ?? '{}', true);
        $this->assertIsArray($payload2);
        $this->assertArrayNotHasKey('password', $payload2);
        $this->assertArrayHasKey('receipt-data', $payload2);
    }
}
