<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\APIError;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    private Validator $validator;
    private ClientInterface&MockObject $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createMock(ClientInterface::class);
        $signingKey = (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $this->validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX,
        );
        $this->validator->setHttpClient($this->mockClient);
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateReturnsResponse(): void
    {
        $json = (string) file_get_contents(__DIR__ . '/fixtures/transactionHistoryResponse.json');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains((string) $request->getUri(), '/inApps/v2/history/abc123');
            }))
            ->willReturn(new GuzzleResponse(200, [], $json));

        $this->validator->validate('abc123');
    }

    /**
     * @covers ::requestTestNotification
     * @covers ::makeRequest
     */
    public function testRequestTestNotificationReturnsToken(): void
    {
        $mockResponseBody = json_encode(['testNotificationToken' => 'test-token-123'], JSON_THROW_ON_ERROR);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'POST'
                    && str_contains((string) $request->getUri(), '/inApps/v1/notifications/test');
            }))
            ->willReturn(new GuzzleResponse(200, [], $mockResponseBody));

        $token = $this->validator->requestTestNotification();
        self::assertSame('test-token-123', $token);
    }

    /**
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateThrowsWithKnownErrorCode(): void
    {
        $json = json_encode([
            'errorCode'    => APIError::INVALID_TRANSACTION_ID->value,
            'errorMessage' => 'Invalid transaction ID provided',
        ], JSON_THROW_ON_ERROR);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(400, [], $json));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(APIError::INVALID_TRANSACTION_ID->value);

        $this->validator->validate('bad-id');
    }

    /**
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateHandles401Unauthorized(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('App Store API error [401]: Unauthenticated');

        $this->validator->validate('fake-transaction-id');
    }

    /**
     * @covers ::validate
     * @covers ::makeRequest
     */
    public function testValidateHandles404NotFound(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(404);
        $this->expectExceptionMessage('App Store API error [404]: Not Found');

        $this->validator->validate('does-not-exist');
    }

    /**
     * @covers ::validate
     */
    public function testValidateThrowsWhenTransactionIdMissing(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing transaction ID for App Store Server API validation.');
        // no setTransactionId() and no param
        $this->validator->validate();
    }

    /**
     * @covers ::makeRequest
     */
    public function testInvalidJsonBodyThrows(): void
    {
        // Return a 200 with invalid JSON -> should throw invalid format error
        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(200, [], '{not-json'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid response format from App Store Server API');

        $this->validator->validate('abc123');
    }
}
