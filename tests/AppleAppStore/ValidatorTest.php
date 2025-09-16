<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\APIError;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group      apple-app-store
 * @coversDefaultClass \ReceiptValidator\AppleAppStore\Validator
 */
class ValidatorTest extends TestCase
{
    private Validator|MockInterface $validator;
    private Client|MockInterface $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = Mockery::mock(Client::class);
        $signingKey = file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        // Use a partial mock to intercept the getClient method
        $this->validator = Mockery::mock(Validator::class, [
            $signingKey,
            'ABC123XYZ',
            'DEF456UVW',
            'com.example',
            Environment::SANDBOX
        ])->makePartial();

        $this->validator->shouldReceive('__construct')->passthru();
        $this->validator->shouldAllowMockingProtectedMethods();
        $this->validator->shouldReceive('getClient')->andReturn($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateReturnsResponse(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/transactionHistoryResponse.json');
        $this->mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(200, [], $json));

        $response = $this->validator->validate('abc123');
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @covers ::requestTestNotification
     * @covers ::makeRequest
     */
    public function testRequestTestNotificationReturnsToken(): void
    {
        $mockResponseBody = json_encode(['testNotificationToken' => 'test-token-123']);
        $this->mockClient->shouldReceive('request')
            ->once()
            ->with('POST', '/inApps/v1/notifications/test', Mockery::any())
            ->andReturn(new GuzzleResponse(200, [], $mockResponseBody));

        $token = $this->validator->requestTestNotification();
        $this->assertEquals('test-token-123', $token);
    }

    /**
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateThrowsWithKnownErrorCode(): void
    {
        $json = json_encode([
            'errorCode' => APIError::INVALID_TRANSACTION_ID->value,
            'errorMessage' => 'Invalid transaction ID provided'
        ]);
        $this->mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(400, [], $json));

        $this->expectException(ValidationException::class);
        // FIX: Use ->value to get the integer from the enum case
        $this->expectExceptionCode(APIError::INVALID_TRANSACTION_ID->value);

        $this->validator->validate('bad-id');
    }

    /**
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateHandles401Unauthorized(): void
    {
        $this->mockClient->shouldReceive('request')
            ->once()
            ->andReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('App Store API error [401]: Unauthenticated');

        $this->validator->validate('fake-transaction-id');
    }
}
