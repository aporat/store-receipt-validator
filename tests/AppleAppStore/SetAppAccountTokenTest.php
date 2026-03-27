<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\APIError;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
final class SetAppAccountTokenTest extends TestCase
{
    private const VALID_UUID  = 'a1b2c3d4-e5f6-4789-a012-b3c4d5e6f701';
    private const TRANSACTION = 'txn-abc123';

    private function makeValidator(ClientInterface $client): Validator
    {
        $signingKey = (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

        $validator = new Validator(
            signingKey: $signingKey,
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX,
        );
        $validator->setHttpClient($client);

        return $validator;
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::setAppAccountToken
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testSetAppAccountTokenCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'PUT'
                    && str_contains(
                        (string) $request->getUri(),
                        '/inApps/v2/transactions/' . self::TRANSACTION . '/appAccountToken'
                    );
            }))
            ->willReturn(new GuzzleResponse(200, [], ''));

        $validator->setAppAccountToken(self::TRANSACTION, self::VALID_UUID);

        // Reaching here without exception means success.
        $this->addToAssertionCount(1);
    }

    public function testSetAppAccountTokenSendsJsonBody(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body    = (string) $request->getBody();
                $decoded = json_decode($body, true);

                return is_array($decoded)
                    && ($decoded['appAccountToken'] ?? null) === self::VALID_UUID
                    && str_contains($request->getHeaderLine('Content-Type'), 'application/json');
            }))
            ->willReturn(new GuzzleResponse(200, [], ''));

        $validator->setAppAccountToken(self::TRANSACTION, self::VALID_UUID);
    }

    /**
     * Verifies that a 200 with an empty body does not throw and returns void cleanly.
     */
    public function testEmptyBodyOn200DoesNotThrow(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(200, [], ''));

        $validator->setAppAccountToken(self::TRANSACTION, self::VALID_UUID);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // UUID validation
    // -------------------------------------------------------------------------

    #[DataProvider('invalidUuidProvider')]
    public function testThrowsOnInvalidUuid(string $invalidUuid): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient->expects($this->never())->method('sendRequest');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('appAccountToken must be a valid UUID v4');

        $validator->setAppAccountToken(self::TRANSACTION, $invalidUuid);
    }

    public static function invalidUuidProvider(): array
    {
        return [
            'empty string'           => [''],
            'not a uuid'             => ['not-a-uuid'],
            'uuid v1 (wrong version)'=> ['550e8400-e29b-11d4-a716-446655440000'],
            'uuid v5 (wrong version)'=> ['a1b2c3d4-e5f6-5789-a012-b3c4d5e6f701'],
            'wrong variant bit'      => ['a1b2c3d4-e5f6-4789-0012-b3c4d5e6f701'],
            'too short'              => ['a1b2c3d4-e5f6-4789-a012'],
            'no hyphens'             => ['a1b2c3d4e5f64789a012b3c4d5e6f701'],
        ];
    }

    #[DataProvider('validUuidProvider')]
    public function testAcceptsValidUuidV4(string $uuid): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(200, [], ''));

        $validator->setAppAccountToken(self::TRANSACTION, $uuid);
        $this->addToAssertionCount(1);
    }

    public static function validUuidProvider(): array
    {
        return [
            'lowercase' => ['a1b2c3d4-e5f6-4789-a012-b3c4d5e6f701'],
            'uppercase' => ['A1B2C3D4-E5F6-4789-A012-B3C4D5E6F701'],
            'variant 9' => ['a1b2c3d4-e5f6-4789-9012-b3c4d5e6f701'],
            'variant b' => ['a1b2c3d4-e5f6-4789-b012-b3c4d5e6f701'],
        ];
    }

    // -------------------------------------------------------------------------
    // Error handling
    // -------------------------------------------------------------------------

    public function testThrowsOnApiError(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(400, [], json_encode([
                'errorCode'    => APIError::INVALID_TRANSACTION_ID->value,
                'errorMessage' => 'Invalid transaction ID',
            ], JSON_THROW_ON_ERROR)));

        $this->expectException(ValidationException::class);
        $this->expectExceptionCode(APIError::INVALID_TRANSACTION_ID->value);

        $validator->setAppAccountToken(self::TRANSACTION, self::VALID_UUID);
    }

    public function testThrowsOn401(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unauthenticated');

        $validator->setAppAccountToken(self::TRANSACTION, self::VALID_UUID);
    }
}
