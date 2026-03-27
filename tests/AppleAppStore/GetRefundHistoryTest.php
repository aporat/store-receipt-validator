<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\RefundHistoryResponse;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(RefundHistoryResponse::class)]
final class GetRefundHistoryTest extends TestCase
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
     * @covers \ReceiptValidator\AppleAppStore\Validator::getRefundHistory
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testGetRefundHistoryCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains((string) $request->getUri(), '/inApps/v2/refund/lookup/txn-abc123');
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'signedTransactions' => [],
                'revision'           => null,
                'hasMore'            => false,
            ], JSON_THROW_ON_ERROR)));

        $response = $this->validator->getRefundHistory('txn-abc123');
        self::assertInstanceOf(RefundHistoryResponse::class, $response);
    }

    public function testGetRefundHistoryPassesRevisionQueryParam(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), 'revision=page2token');
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'signedTransactions' => [],
                'hasMore'            => false,
            ], JSON_THROW_ON_ERROR)));

        $this->validator->getRefundHistory('txn-abc123', 'page2token');
    }

    public function testGetRefundHistoryThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unauthenticated');
        $this->validator->getRefundHistory('txn-abc123');
    }

    // -------------------------------------------------------------------------
    // RefundHistoryResponse parsing
    // -------------------------------------------------------------------------

    public function testRefundHistoryResponseParsesEmptyTransactions(): void
    {
        $response = new RefundHistoryResponse([
            'signedTransactions' => [],
            'revision'           => 'token-abc',
            'hasMore'            => true,
        ]);

        self::assertSame([], $response->getSignedTransactions());
        self::assertSame('token-abc', $response->getRevision());
        self::assertTrue($response->hasMore());
    }

    public function testRefundHistoryResponseHandlesMissingFields(): void
    {
        $response = new RefundHistoryResponse([]);

        self::assertSame([], $response->getSignedTransactions());
        self::assertNull($response->getRevision());
        self::assertFalse($response->hasMore());
    }

    public function testRefundHistoryResponseSkipsInvalidJwsTokens(): void
    {
        $response = new RefundHistoryResponse([
            'signedTransactions' => ['not-a-valid-jws', '', 42],
            'hasMore'            => false,
        ]);

        // Invalid tokens are silently skipped
        self::assertSame([], $response->getSignedTransactions());
    }
}
