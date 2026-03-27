<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\MassExtendRenewalDateRequest;
use ReceiptValidator\AppleAppStore\MassExtendRenewalDateStatusResponse;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(MassExtendRenewalDateRequest::class)]
#[CoversClass(MassExtendRenewalDateStatusResponse::class)]
final class MassExtendRenewalDateTest extends TestCase
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

    // -------------------------------------------------------------------------
    // extendSubscriptionRenewalDatesForAllActiveSubscribers
    // -------------------------------------------------------------------------

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::extendSubscriptionRenewalDatesForAllActiveSubscribers
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testMassExtendCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'POST'
                    && str_contains((string) $request->getUri(), '/inApps/v1/subscriptions/extend/mass')
                    && !str_contains((string) $request->getUri(), '/inApps/v1/subscriptions/extend/mass/');
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'requestIdentifier' => 'req-uuid-abc',
            ], JSON_THROW_ON_ERROR)));

        $request                    = new MassExtendRenewalDateRequest();
        $request->extendByDays      = 30;
        $request->productId         = 'com.example.pro';
        $request->requestIdentifier = 'req-uuid-abc';

        $id = $this->validator->extendSubscriptionRenewalDatesForAllActiveSubscribers($request);
        self::assertSame('req-uuid-abc', $id);
    }

    public function testMassExtendSendsJsonBody(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $decoded = json_decode((string) $request->getBody(), true);

                return is_array($decoded)
                    && ($decoded['extendByDays'] ?? null) === 15
                    && ($decoded['productId'] ?? null) === 'com.example.monthly'
                    && ($decoded['storefrontCountryCodes'] ?? null) === ['US', 'GB']
                    && str_contains($request->getHeaderLine('Content-Type'), 'application/json');
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'requestIdentifier' => 'req-1',
            ], JSON_THROW_ON_ERROR)));

        $request                         = new MassExtendRenewalDateRequest();
        $request->extendByDays           = 15;
        $request->productId              = 'com.example.monthly';
        $request->storefrontCountryCodes = ['US', 'GB'];

        $this->validator->extendSubscriptionRenewalDatesForAllActiveSubscribers($request);
    }

    public function testMassExtendThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $this->validator->extendSubscriptionRenewalDatesForAllActiveSubscribers(new MassExtendRenewalDateRequest());
    }

    // -------------------------------------------------------------------------
    // getStatusOfSubscriptionRenewalDateExtensions
    // -------------------------------------------------------------------------

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::getStatusOfSubscriptionRenewalDateExtensions
     */
    public function testGetStatusCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains(
                        (string) $request->getUri(),
                        '/inApps/v1/subscriptions/extend/mass/com.example.pro/req-uuid-abc'
                    );
            }))
            ->willReturn(new GuzzleResponse(200, [], json_encode([
                'requestIdentifier' => 'req-uuid-abc',
                'complete'          => true,
                'completeDate'      => 1700000000000,
                'succeededCount'    => 150,
                'failedCount'       => 2,
            ], JSON_THROW_ON_ERROR)));

        $response = $this->validator->getStatusOfSubscriptionRenewalDateExtensions('com.example.pro', 'req-uuid-abc');
        self::assertInstanceOf(MassExtendRenewalDateStatusResponse::class, $response);
    }

    public function testGetStatusThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(404, [], ''));

        $this->expectException(ValidationException::class);
        $this->validator->getStatusOfSubscriptionRenewalDateExtensions('com.example.pro', 'req-uuid-abc');
    }

    // -------------------------------------------------------------------------
    // MassExtendRenewalDateStatusResponse parsing
    // -------------------------------------------------------------------------

    public function testStatusResponseParsesFields(): void
    {
        $response = new MassExtendRenewalDateStatusResponse([
            'requestIdentifier' => 'req-uuid-abc',
            'complete'          => true,
            'completeDate'      => 1700000000000,
            'succeededCount'    => 150,
            'failedCount'       => 2,
        ]);

        self::assertSame('req-uuid-abc', $response->getRequestIdentifier());
        self::assertTrue($response->isComplete());
        self::assertNotNull($response->getCompleteDate());
        self::assertSame(150, $response->getSucceededCount());
        self::assertSame(2, $response->getFailedCount());
    }

    public function testStatusResponseHandlesMissingFields(): void
    {
        $response = new MassExtendRenewalDateStatusResponse([]);

        self::assertNull($response->getRequestIdentifier());
        self::assertFalse($response->isComplete());
        self::assertNull($response->getCompleteDate());
        self::assertNull($response->getSucceededCount());
        self::assertNull($response->getFailedCount());
    }

    // -------------------------------------------------------------------------
    // MassExtendRenewalDateRequest serialization
    // -------------------------------------------------------------------------

    public function testRequestToArrayOmitsNullFields(): void
    {
        $request = new MassExtendRenewalDateRequest();
        self::assertSame([], $request->toArray());
    }

    public function testRequestToArrayIncludesSetFields(): void
    {
        $request                         = new MassExtendRenewalDateRequest();
        $request->extendByDays           = 30;
        $request->extendReasonCode       = 1;
        $request->requestIdentifier      = 'req-uuid-xyz';
        $request->productId              = 'com.example.pro';
        $request->storefrontCountryCodes = ['US', 'CA'];

        $body = $request->toArray();
        self::assertSame(30, $body['extendByDays']);
        self::assertSame(1, $body['extendReasonCode']);
        self::assertSame('req-uuid-xyz', $body['requestIdentifier']);
        self::assertSame('com.example.pro', $body['productId']);
        self::assertSame(['US', 'CA'], $body['storefrontCountryCodes']);
    }
}
