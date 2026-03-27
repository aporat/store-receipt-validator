<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
final class GetTransactionInfoTest extends TestCase
{
    private Validator $validator;
    private ClientInterface $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = $this->createStub(ClientInterface::class);
        $signingKey       = (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8');

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
     * @covers \ReceiptValidator\AppleAppStore\Validator::getTransactionInfo
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testGetTransactionInfoCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains((string) $request->getUri(), '/inApps/v2/transactions/txn-abc123')
                    && !str_contains((string) $request->getUri(), 'appTransaction')
                    && !str_contains((string) $request->getUri(), 'appAccountToken');
            }))
            ->willReturn(new GuzzleResponse(400, [], json_encode([
                'errorCode'    => 4290000,
                'errorMessage' => 'Rate limit exceeded',
            ], JSON_THROW_ON_ERROR)));

        // We only care that the correct endpoint was hit; the 400 triggers an exception.
        $this->expectException(ValidationException::class);
        $this->validator->getTransactionInfo('txn-abc123');
    }

    public function testGetTransactionInfoThrowsWhenSignedTransactionInfoMissing(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(200, [], json_encode([], JSON_THROW_ON_ERROR)));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing or invalid signedTransactionInfo');
        $this->validator->getTransactionInfo('txn-abc123');
    }

    public function testGetTransactionInfoThrowsOn401(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unauthenticated');
        $this->validator->getTransactionInfo('txn-abc123');
    }

    public function testGetTransactionInfoThrowsOn404(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Not Found');
        $this->validator->getTransactionInfo('txn-not-found');
    }
}
