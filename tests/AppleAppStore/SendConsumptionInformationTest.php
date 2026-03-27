<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\ConsumptionRequest;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(ConsumptionRequest::class)]
final class SendConsumptionInformationTest extends TestCase
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
     * @covers \ReceiptValidator\AppleAppStore\Validator::sendConsumptionInformation
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testSendConsumptionInformationCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'PUT'
                    && str_contains((string) $request->getUri(), '/inApps/v2/transactions/consumption/txn-abc123');
            }))
            ->willReturn(new GuzzleResponse(200, [], ''));

        $request = new ConsumptionRequest(customerConsented: true, sampleContentProvided: false);
        $this->validator->sendConsumptionInformation('txn-abc123', $request);

        $this->addToAssertionCount(1);
    }

    public function testSendConsumptionInformationSendsCorrectJsonBody(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $decoded = json_decode((string) $request->getBody(), true);

                return is_array($decoded)
                    && ($decoded['customerConsented'] ?? null) === true
                    && ($decoded['sampleContentProvided'] ?? null) === false
                    && ($decoded['deliveryStatus'] ?? null) === 0
                    && ($decoded['consumptionPercentage'] ?? null) === 50
                    && ($decoded['refundPreference'] ?? null) === 1
                    && str_contains($request->getHeaderLine('Content-Type'), 'application/json');
            }))
            ->willReturn(new GuzzleResponse(200, [], ''));

        $request                       = new ConsumptionRequest(customerConsented: true, sampleContentProvided: false);
        $request->deliveryStatus       = 0;
        $request->consumptionPercentage = 50;
        $request->refundPreference     = 1;

        $this->validator->sendConsumptionInformation('txn-abc123', $request);
    }

    public function testSendConsumptionInformationThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $request = new ConsumptionRequest(customerConsented: true, sampleContentProvided: true);
        $this->validator->sendConsumptionInformation('txn-abc123', $request);
    }

    // -------------------------------------------------------------------------
    // ConsumptionRequest serialization
    // -------------------------------------------------------------------------

    public function testRequestToArrayIncludesRequiredFields(): void
    {
        $request = new ConsumptionRequest(customerConsented: true, sampleContentProvided: false);
        $body    = $request->toArray();

        self::assertTrue($body['customerConsented']);
        self::assertFalse($body['sampleContentProvided']);
        self::assertArrayNotHasKey('deliveryStatus', $body);
        self::assertArrayNotHasKey('consumptionPercentage', $body);
        self::assertArrayNotHasKey('refundPreference', $body);
    }

    public function testRequestToArrayIncludesOptionalFieldsWhenSet(): void
    {
        $request                        = new ConsumptionRequest(customerConsented: false, sampleContentProvided: true);
        $request->deliveryStatus        = 2;
        $request->consumptionPercentage = 80;
        $request->refundPreference      = 2;

        $body = $request->toArray();

        self::assertFalse($body['customerConsented']);
        self::assertTrue($body['sampleContentProvided']);
        self::assertSame(2, $body['deliveryStatus']);
        self::assertSame(80, $body['consumptionPercentage']);
        self::assertSame(2, $body['refundPreference']);
    }
}
