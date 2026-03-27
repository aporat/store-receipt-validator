<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\TransactionHistoryParams;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;

/**
 * @group apple-app-store
 */
#[CoversClass(TransactionHistoryParams::class)]
#[CoversClass(Validator::class)]
final class TransactionHistoryParamsTest extends TestCase
{
    // -------------------------------------------------------------------------
    // TransactionHistoryParams::toQueryParams()
    // -------------------------------------------------------------------------

    public function testDefaultsToDescendingSort(): void
    {
        $p = new TransactionHistoryParams();
        self::assertSame('DESCENDING', $p->toQueryParams()['sort']);
    }

    public function testOnlySortKeyPresentByDefault(): void
    {
        $p      = new TransactionHistoryParams();
        $params = $p->toQueryParams();

        self::assertArrayHasKey('sort', $params);
        self::assertCount(1, $params);
    }

    public function testRevisionIncludedWhenSet(): void
    {
        $p           = new TransactionHistoryParams();
        $p->revision = 'rev-token-xyz';

        self::assertSame('rev-token-xyz', $p->toQueryParams()['revision']);
    }

    public function testDateRangeIncludedWhenSet(): void
    {
        $p            = new TransactionHistoryParams();
        $p->startDate = 1700000000000;
        $p->endDate   = 1710000000000;
        $params       = $p->toQueryParams();

        self::assertSame(1700000000000, $params['startDate']);
        self::assertSame(1710000000000, $params['endDate']);
    }

    public function testProductIdArrayIncluded(): void
    {
        $p            = new TransactionHistoryParams();
        $p->productId = ['com.example.sub.monthly', 'com.example.sub.annual'];

        self::assertSame(['com.example.sub.monthly', 'com.example.sub.annual'], $p->toQueryParams()['productId']);
    }

    public function testProductTypeArrayIncluded(): void
    {
        $p              = new TransactionHistoryParams();
        $p->productType = [
            TransactionHistoryParams::PRODUCT_TYPE_AUTO_RENEWABLE_SUBSCRIPTION,
            TransactionHistoryParams::PRODUCT_TYPE_CONSUMABLE,
        ];

        self::assertSame(
            [
                TransactionHistoryParams::PRODUCT_TYPE_AUTO_RENEWABLE_SUBSCRIPTION,
                TransactionHistoryParams::PRODUCT_TYPE_CONSUMABLE,
            ],
            $p->toQueryParams()['productType']
        );
    }

    public function testSubscriptionGroupIdentifierArrayIncluded(): void
    {
        $p                               = new TransactionHistoryParams();
        $p->subscriptionGroupIdentifier = ['group-1', 'group-2'];

        self::assertSame(['group-1', 'group-2'], $p->toQueryParams()['subscriptionGroupIdentifier']);
    }

    public function testInAppOwnershipTypeIncluded(): void
    {
        $p                     = new TransactionHistoryParams();
        $p->inAppOwnershipType = TransactionHistoryParams::OWNERSHIP_FAMILY_SHARED;

        self::assertSame(TransactionHistoryParams::OWNERSHIP_FAMILY_SHARED, $p->toQueryParams()['inAppOwnershipType']);
    }

    #[DataProvider('revokedProvider')]
    public function testRevokedSerialisation(?bool $value, ?string $expected): void
    {
        $p          = new TransactionHistoryParams();
        $p->revoked = $value;
        $params     = $p->toQueryParams();

        if ($expected === null) {
            self::assertArrayNotHasKey('revoked', $params);
        } else {
            self::assertSame($expected, $params['revoked']);
        }
    }

    public static function revokedProvider(): array
    {
        return [
            'null — key absent' => [null,  null],
            'true  → "true"'    => [true,  'true'],
            'false → "false"'   => [false, 'false'],
        ];
    }

    public function testEmptyArraysAreOmitted(): void
    {
        $p              = new TransactionHistoryParams();
        $p->productId   = [];
        $p->productType = [];

        $params = $p->toQueryParams();

        self::assertArrayNotHasKey('productId',   $params);
        self::assertArrayNotHasKey('productType',  $params);
    }

    // -------------------------------------------------------------------------
    // Validator::validate() integration
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

    private function historyJson(): string
    {
        return (string) file_get_contents(__DIR__ . '/fixtures/transactionHistoryResponse.json');
    }

    public function testValidateWithNoParamsUsesDescendingSort(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), 'sort=DESCENDING');
            }))
            ->willReturn(new GuzzleResponse(200, [], $this->historyJson()));

        $validator->validate('txn-123');
    }

    public function testValidateWithAscendingSortPassedThrough(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $params       = new TransactionHistoryParams();
        $params->sort = TransactionHistoryParams::SORT_ASCENDING;

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), 'sort=ASCENDING');
            }))
            ->willReturn(new GuzzleResponse(200, [], $this->historyJson()));

        $validator->validate('txn-123', $params);
    }

    public function testValidateWithRevisionPassedThrough(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $params           = new TransactionHistoryParams();
        $params->revision = 'page-token-abc';

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $uri = (string) $request->getUri();
                return str_contains($uri, 'revision=page-token-abc');
            }))
            ->willReturn(new GuzzleResponse(200, [], $this->historyJson()));

        $validator->validate('txn-123', $params);
    }

    public function testValidateWithMultipleProductIdsUsesRepeatedKeys(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $validator  = $this->makeValidator($mockClient);

        $params            = new TransactionHistoryParams();
        $params->productId = ['com.example.sub.a', 'com.example.sub.b'];

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                $uri = (string) $request->getUri();
                // Apple expects repeated keys, NOT bracket notation
                return str_contains($uri, 'productId=com.example.sub.a')
                    && str_contains($uri, 'productId=com.example.sub.b')
                    && !str_contains($uri, 'productId%5B');
            }))
            ->willReturn(new GuzzleResponse(200, [], $this->historyJson()));

        $validator->validate('txn-123', $params);
    }
}
