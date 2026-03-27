<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\LastTransactionItem;
use ReceiptValidator\AppleAppStore\SubscriptionGroupStatusItem;
use ReceiptValidator\AppleAppStore\SubscriptionStatus;
use ReceiptValidator\AppleAppStore\SubscriptionStatusResponse;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;

/**
 * @group apple-app-store
 */
#[CoversClass(SubscriptionStatusResponse::class)]
#[CoversClass(SubscriptionGroupStatusItem::class)]
#[CoversClass(LastTransactionItem::class)]
#[CoversClass(SubscriptionStatus::class)]
#[CoversClass(Validator::class)]
final class SubscriptionStatusTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
    // Validator integration
    // -------------------------------------------------------------------------

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::getAllSubscriptionStatuses
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testGetAllSubscriptionStatusesCallsCorrectEndpoint(): void
    {
        $json       = (string) file_get_contents(__DIR__ . '/fixtures/subscriptionStatusResponse.json');
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains((string) $request->getUri(), '/inApps/v2/subscriptions/1000000000000001');
            }))
            ->willReturn(new GuzzleResponse(200, [], $json));

        $response = $validator->getAllSubscriptionStatuses('1000000000000001');

        self::assertInstanceOf(SubscriptionStatusResponse::class, $response);
    }

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::getAllSubscriptionStatuses
     */
    public function testGetAllSubscriptionStatusesReturnsParsedData(): void
    {
        $json       = (string) file_get_contents(__DIR__ . '/fixtures/subscriptionStatusResponse.json');
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(200, [], $json));

        $response = $validator->getAllSubscriptionStatuses('1000000000000001');

        self::assertSame(Environment::SANDBOX, $response->getEnvironment());
        self::assertSame('com.example.app', $response->getBundleId());
        self::assertSame(123456, $response->getAppAppleId());
        self::assertCount(2, $response->getData());
        self::assertCount(3, $response->getAllLastTransactions());
    }

    // -------------------------------------------------------------------------
    // SubscriptionStatusResponse
    // -------------------------------------------------------------------------

    #[DataProvider('responseProvider')]
    public function testResponseParsing(array $raw, array $expected): void
    {
        $response = new SubscriptionStatusResponse($raw);

        self::assertSame($expected['environment'], $response->getEnvironment());
        self::assertSame($expected['bundleId'],    $response->getBundleId());
        self::assertSame($expected['appAppleId'],  $response->getAppAppleId());
        self::assertCount($expected['groupCount'], $response->getData());
        self::assertCount($expected['totalTxCount'], $response->getAllLastTransactions());
    }

    public static function responseProvider(): array
    {
        return [
            'full response' => [
                'raw' => [
                    'environment' => 'Sandbox',
                    'bundleId'    => 'com.example.app',
                    'appAppleId'  => 999,
                    'data'        => [
                        [
                            'subscriptionGroupIdentifier' => 'group-1',
                            'lastTransactions'            => [
                                ['originalTransactionId' => 'tx-1', 'status' => 1],
                                ['originalTransactionId' => 'tx-2', 'status' => 2],
                            ],
                        ],
                        [
                            'subscriptionGroupIdentifier' => 'group-2',
                            'lastTransactions'            => [
                                ['originalTransactionId' => 'tx-3', 'status' => 5],
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    'environment'   => Environment::SANDBOX,
                    'bundleId'      => 'com.example.app',
                    'appAppleId'    => 999,
                    'groupCount'    => 2,
                    'totalTxCount'  => 3,
                ],
            ],
            'empty response' => [
                'raw'      => [],
                'expected' => [
                    'environment'  => Environment::PRODUCTION,
                    'bundleId'     => null,
                    'appAppleId'   => null,
                    'groupCount'   => 0,
                    'totalTxCount' => 0,
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // SubscriptionGroupStatusItem
    // -------------------------------------------------------------------------

    public function testGroupItemParsesIdentifierAndTransactions(): void
    {
        $group = new SubscriptionGroupStatusItem([
            'subscriptionGroupIdentifier' => 'my-group-99',
            'lastTransactions'            => [
                ['originalTransactionId' => 'tx-a', 'status' => 1],
                ['originalTransactionId' => 'tx-b', 'status' => 3],
            ],
        ]);

        self::assertSame('my-group-99', $group->getSubscriptionGroupIdentifier());
        self::assertCount(2, $group->getLastTransactions());
    }

    public function testGroupItemHandlesMissingLastTransactions(): void
    {
        $group = new SubscriptionGroupStatusItem([
            'subscriptionGroupIdentifier' => 'g-empty',
        ]);

        self::assertSame('g-empty', $group->getSubscriptionGroupIdentifier());
        self::assertCount(0, $group->getLastTransactions());
    }

    // -------------------------------------------------------------------------
    // LastTransactionItem
    // -------------------------------------------------------------------------

    public function testLastTransactionItemParsesStatusAndOriginalId(): void
    {
        $item = new LastTransactionItem([
            'originalTransactionId' => 'tx-abc',
            'status'                => 2,
        ]);

        self::assertSame('tx-abc', $item->getOriginalTransactionId());
        self::assertSame(SubscriptionStatus::Expired, $item->getStatus());
        self::assertNull($item->getTransactionInfo());
        self::assertNull($item->getRenewalInfo());
    }

    public function testLastTransactionItemHandlesMissingSignedTokens(): void
    {
        $item = new LastTransactionItem([
            'originalTransactionId' => 'tx-no-jws',
            'status'                => 4,
        ]);

        self::assertNull($item->getTransactionInfo());
        self::assertNull($item->getRenewalInfo());
    }

    // -------------------------------------------------------------------------
    // SubscriptionStatus enum
    // -------------------------------------------------------------------------

    /**
     * @covers \ReceiptValidator\AppleAppStore\SubscriptionStatus::label
     */
    public function testAllStatusValuesHaveLabel(): void
    {
        foreach (SubscriptionStatus::cases() as $case) {
            self::assertNotEmpty($case->label(), "label() should not be empty for {$case->name}");
        }
    }

    #[DataProvider('statusProvider')]
    public function testStatusFromInt(int $value, SubscriptionStatus $expected): void
    {
        self::assertSame($expected, SubscriptionStatus::from($value));
    }

    public static function statusProvider(): array
    {
        return [
            'Active'               => [1, SubscriptionStatus::Active],
            'Expired'              => [2, SubscriptionStatus::Expired],
            'InBillingRetryPeriod' => [3, SubscriptionStatus::InBillingRetryPeriod],
            'InBillingGracePeriod' => [4, SubscriptionStatus::InBillingGracePeriod],
            'Revoked'              => [5, SubscriptionStatus::Revoked],
        ];
    }
}
