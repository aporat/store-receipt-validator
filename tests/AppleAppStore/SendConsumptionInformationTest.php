<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\ConsumptionRequest;
use ReceiptValidator\AppleAppStore\ConsumptionRequestV1;
use ReceiptValidator\AppleAppStore\DeliveryStatus;
use ReceiptValidator\AppleAppStore\RefundPreference;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
#[CoversClass(ConsumptionRequest::class)]
#[CoversClass(ConsumptionRequestV1::class)]
#[CoversClass(DeliveryStatus::class)]
#[CoversClass(RefundPreference::class)]
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
     * @param callable(RequestInterface): bool $matcher
     */
    private function expectRequest(callable $matcher): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback($matcher))
            ->willReturn(new GuzzleResponse(200, [], ''));
    }

    // -------------------------------------------------------------------------
    // v2 endpoint
    // -------------------------------------------------------------------------

    public function testSendConsumptionInformationCallsV2Endpoint(): void
    {
        $this->expectRequest(static fn (RequestInterface $request): bool =>
            $request->getMethod() === 'PUT'
            && str_contains((string) $request->getUri(), '/inApps/v2/transactions/consumption/txn-abc123'));

        $request = new ConsumptionRequest(
            customerConsented: true,
            sampleContentProvided: false,
            deliveryStatus: DeliveryStatus::DELIVERED,
        );
        $this->validator->sendConsumptionInformation('txn-abc123', $request);

        $this->addToAssertionCount(1);
    }

    public function testSendConsumptionInformationSendsV2JsonBody(): void
    {
        $this->expectRequest(static function (RequestInterface $request): bool {
            $decoded = json_decode((string) $request->getBody(), true);

            return is_array($decoded)
                && ($decoded['customerConsented'] ?? null) === true
                && ($decoded['sampleContentProvided'] ?? null) === false
                && ($decoded['deliveryStatus'] ?? null) === 'DELIVERED'
                && ($decoded['consumptionPercentage'] ?? null) === 50000
                && ($decoded['refundPreference'] ?? null) === 'GRANT_PRORATED'
                && str_contains($request->getHeaderLine('Content-Type'), 'application/json');
        });

        $request = new ConsumptionRequest(customerConsented: true, sampleContentProvided: false);
        $request->deliveryStatus   = DeliveryStatus::DELIVERED;
        $request->refundPreference = RefundPreference::GRANT_PRORATED;
        $request->setConsumptionPercent(50);

        $this->validator->sendConsumptionInformation('txn-abc123', $request);
    }

    public function testSendConsumptionInformationRejectsMissingDeliveryStatus(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->never())->method('sendRequest');
        $this->validator->setHttpClient($mockClient);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('deliveryStatus is required');

        $request = new ConsumptionRequest(customerConsented: true, sampleContentProvided: false);
        $this->validator->sendConsumptionInformation('txn-abc123', $request);
    }

    public function testSendConsumptionInformationThrowsOnApiError(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(401, [], ''));

        $this->expectException(ValidationException::class);
        $request = new ConsumptionRequest(true, true, DeliveryStatus::DELIVERED);
        $this->validator->sendConsumptionInformation('txn-abc123', $request);
    }

    // -------------------------------------------------------------------------
    // v1 endpoint (deprecated)
    // -------------------------------------------------------------------------

    public function testSendConsumptionInformationWithV1RequestCallsV1Endpoint(): void
    {
        $this->expectRequest(static function (RequestInterface $request): bool {
            $decoded = json_decode((string) $request->getBody(), true);

            return $request->getMethod() === 'PUT'
                && str_contains((string) $request->getUri(), '/inApps/v1/transactions/consumption/txn-abc123')
                && is_array($decoded)
                && ($decoded['customerConsented'] ?? null) === true
                && ($decoded['deliveryStatus'] ?? null) === 0
                && ($decoded['consumptionStatus'] ?? null) === 3
                && !array_key_exists('platform', $decoded);
        });

        $request                    = new ConsumptionRequestV1(customerConsented: true, sampleContentProvided: false);
        $request->deliveryStatus    = 0;
        $request->consumptionStatus = 3;

        $this->validator->sendConsumptionInformation('txn-abc123', $request);

        $this->addToAssertionCount(1);
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

    public function testRequestToArraySerializesEnumsToStrings(): void
    {
        $request                        = new ConsumptionRequest(customerConsented: false, sampleContentProvided: true);
        $request->deliveryStatus        = DeliveryStatus::UNDELIVERED_WRONG_ITEM;
        $request->consumptionPercentage = 0;
        $request->refundPreference      = RefundPreference::GRANT_FULL;

        $body = $request->toArray();

        self::assertFalse($body['customerConsented']);
        self::assertTrue($body['sampleContentProvided']);
        self::assertSame('UNDELIVERED_WRONG_ITEM', $body['deliveryStatus']);
        self::assertSame(0, $body['consumptionPercentage']);
        self::assertSame('GRANT_FULL', $body['refundPreference']);
    }

    public function testRequestMapsLegacyIntegerValuesToV2Strings(): void
    {
        $request                   = new ConsumptionRequest(customerConsented: true, sampleContentProvided: false);
        $request->deliveryStatus   = 2;
        $request->refundPreference = 1;

        $body = $request->toArray();

        self::assertSame('UNDELIVERED_WRONG_ITEM', $body['deliveryStatus']);
        self::assertSame('GRANT_FULL', $body['refundPreference']);
        self::assertSame(DeliveryStatus::UNDELIVERED_WRONG_ITEM, $request->getDeliveryStatus());
        self::assertSame(RefundPreference::GRANT_FULL, $request->getRefundPreference());
    }

    public function testRequestOmitsLegacyUndeclaredRefundPreference(): void
    {
        $request                   = new ConsumptionRequest(true, false, 0);
        $request->refundPreference = 0;

        $body = $request->toArray();

        self::assertSame('DELIVERED', $body['deliveryStatus']);
        self::assertArrayNotHasKey('refundPreference', $body);
    }

    public function testSetConsumptionPercentConvertsToMilliunits(): void
    {
        $request = new ConsumptionRequest(true, false, DeliveryStatus::DELIVERED);

        self::assertSame(67932, $request->setConsumptionPercent(67.932)->consumptionPercentage);
        self::assertSame(100000, $request->setConsumptionPercent(100)->consumptionPercentage);
        self::assertSame(0, $request->setConsumptionPercent(0)->consumptionPercentage);
    }

    public function testSetConsumptionPercentRejectsOutOfRange(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ConsumptionRequest(true, false, DeliveryStatus::DELIVERED))->setConsumptionPercent(101);
    }

    public function testLegacyMappingsCoverEveryV1Value(): void
    {
        self::assertSame(DeliveryStatus::DELIVERED, DeliveryStatus::fromLegacyValue(0));
        self::assertSame(DeliveryStatus::UNDELIVERED_QUALITY_ISSUE, DeliveryStatus::fromLegacyValue(1));
        self::assertSame(DeliveryStatus::UNDELIVERED_WRONG_ITEM, DeliveryStatus::fromLegacyValue(2));
        self::assertSame(DeliveryStatus::UNDELIVERED_SERVER_OUTAGE, DeliveryStatus::fromLegacyValue(3));
        self::assertSame(DeliveryStatus::UNDELIVERED_OTHER, DeliveryStatus::fromLegacyValue(4));
        self::assertSame(DeliveryStatus::UNDELIVERED_OTHER, DeliveryStatus::fromLegacyValue(5));

        self::assertNull(RefundPreference::fromLegacyValue(0));
        self::assertSame(RefundPreference::GRANT_FULL, RefundPreference::fromLegacyValue(1));
        self::assertSame(RefundPreference::DECLINE, RefundPreference::fromLegacyValue(2));
        self::assertNull(RefundPreference::fromLegacyValue(3));
    }

    public function testLegacyMappingRejectsUnknownValue(): void
    {
        $this->expectException(\ValueError::class);
        DeliveryStatus::fromLegacyValue(9);
    }

    public function testV1RequestToArrayOmitsNulls(): void
    {
        $request = new ConsumptionRequestV1();

        self::assertSame([], $request->toArray());

        $request->customerConsented = false;
        $request->userStatus        = 0;

        self::assertSame(['customerConsented' => false, 'userStatus' => 0], $request->toArray());
    }
}
