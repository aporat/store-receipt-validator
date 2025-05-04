<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\APIError;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

class ValidatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testValidateReturnsResponse(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/transactionHistoryResponse.json');

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX
        );

        $validator->client = $mockClient;

        $response = $validator->validate('abc123');
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testRequestTestNotificationReturnsToken(): void
    {
        $mockResponseBody = json_encode(['testNotificationToken' => 'test-token-123']);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->with('POST', 'https://api.storekit-sandbox.itunes.apple.com/inApps/v1/notifications/test', Mockery::on(function ($options) {
                return isset($options['headers']['Authorization']);
            }))
            ->andReturn(new GuzzleResponse(200, [], $mockResponseBody));

        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX
        );

        $validator->client = $mockClient;

        $token = $validator->requestTestNotification();

        $this->assertEquals('test-token-123', $token);
    }

    public function testValidateThrowsWithInvalidTransactionId(): void
    {
        $json = json_encode([
            'errorCode' => APIError::INVALID_TRANSACTION_ID,
            'errorMessage' => 'Invalid transaction ID provided'
        ]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(400, [], $json));

        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX
        );

        $validator->client = $mockClient;

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(APIError::INVALID_TRANSACTION_ID);
        $this->expectExceptionMessageMatches('/4000006/');

        $validator->validate('bad-id');
    }

    public function testValidateThrowsWithUnknownErrorCode(): void
    {
        $json = json_encode([
            'errorCode' => 4999999,
            'errorMessage' => 'Totally unknown error'
        ]);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(400, [], $json));

        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX
        );

        $validator->client = $mockClient;

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(4999999);
        $this->expectExceptionMessageMatches('/4999999.*Totally unknown error/');

        $validator->validate('whatever-id');
    }

    public function testValidateHandles401Unauthorized(): void
    {
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(401, [], ''));

        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX
        );

        $validator->client = $mockClient;

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('Unauthenticated');

        $validator->validate('fake-transaction-id');
    }
}
