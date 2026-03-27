<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\ExtendRenewalDateRequest;
use ReceiptValidator\AppleAppStore\ExtendRenewalDateResponse;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(ExtendRenewalDateRequest::class)]
#[CoversClass(ExtendRenewalDateResponse::class)]
final class ExtendRenewalDateTest extends TestCase
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
     * @covers \ReceiptValidator\AppleAppStore\Validator::extendSubscriptionRenewalDate
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testExtendRenewalDateCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'PUT'
                    && str_contains((string) $request->getUri(), '/inApps/v1/subscriptions/extend/orig-txn-001');
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'originalTransactionId' => 'orig-txn-001',
                'webOrderLineItemId'    => 'woli-123',
                'success'               => true,
                'effectiveDate'         => 1700000000000,
            ], JSON_THROW_ON_ERROR)));

        $request               = new ExtendRenewalDateRequest();
        $request->extendByDays = 30;

        $response = $this->validator->extendSubscriptionRenewalDate('orig-txn-001', $request);
        self::assertInstanceOf(ExtendRenewalDateResponse::class, $response);
    }

    public function testExtendRenewalDateSendsJsonBody(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $body    = (string) $request->getBody();
                $decoded = json_decode($body, true);

                return is_array($decoded)
                    && ($decoded['extendByDays'] ?? null) === 14
                    && ($decoded['extendReasonCode'] ?? null) === 1
                    && str_contains($request->getHeaderLine('Content-Type'), 'application/json');
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'originalTransactionId' => 'orig-txn-001',
                'success'               => true,
            ], JSON_THROW_ON_ERROR)));

        $request                   = new ExtendRenewalDateRequest();
        $request->extendByDays     = 14;
        $request->extendReasonCode = 1;

        $this->validator->extendSubscriptionRenewalDate('orig-txn-001', $request);
    }

    public function testExtendRenewalDateThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->validator->extendSubscriptionRenewalDate('orig-txn-001', new ExtendRenewalDateRequest());
    }

    // -------------------------------------------------------------------------
    // ExtendRenewalDateResponse parsing
    // -------------------------------------------------------------------------

    public function testExtendRenewalDateResponseParsesFields(): void
    {
        $response = new ExtendRenewalDateResponse([
            'originalTransactionId' => 'orig-txn-001',
            'webOrderLineItemId'    => 'woli-abc',
            'success'               => true,
            'effectiveDate'         => 1700000000000,
        ]);

        self::assertSame('orig-txn-001', $response->getOriginalTransactionId());
        self::assertSame('woli-abc', $response->getWebOrderLineItemId());
        self::assertTrue($response->isSuccess());
        self::assertNotNull($response->getEffectiveDate());
    }

    public function testExtendRenewalDateResponseHandlesMissingFields(): void
    {
        $response = new ExtendRenewalDateResponse([]);

        self::assertNull($response->getOriginalTransactionId());
        self::assertNull($response->getWebOrderLineItemId());
        self::assertFalse($response->isSuccess());
        self::assertNull($response->getEffectiveDate());
    }

    // -------------------------------------------------------------------------
    // ExtendRenewalDateRequest serialization
    // -------------------------------------------------------------------------

    public function testRequestToArrayOmitsNullFields(): void
    {
        $request = new ExtendRenewalDateRequest();
        self::assertSame([], $request->toArray());
    }

    public function testRequestToArrayIncludesSetFields(): void
    {
        $request                      = new ExtendRenewalDateRequest();
        $request->extendByDays        = 7;
        $request->extendReasonCode    = 3;
        $request->requestIdentifier   = 'req-uuid-123';

        $body = $request->toArray();
        self::assertSame(7, $body['extendByDays']);
        self::assertSame(3, $body['extendReasonCode']);
        self::assertSame('req-uuid-123', $body['requestIdentifier']);
    }
}
