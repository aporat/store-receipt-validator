<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\CarbonImmutable;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\AppTransaction;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group apple-app-store
 */
#[CoversClass(AppTransaction::class)]
#[CoversClass(Validator::class)]
final class AppTransactionTest extends TestCase
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
    // Validator integration
    // -------------------------------------------------------------------------

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::getAppTransactionInfo
     * @covers \ReceiptValidator\AppleAppStore\Validator::makeRawRequest
     */
    public function testGetAppTransactionInfoCallsCorrectEndpoint(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $this->validator->setHttpClient($mockClient);

        $mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && str_contains((string) $request->getUri(), '/inApps/v2/transactions/appTransaction');
            }))
            ->willReturn(new GuzzleResponse(400, [], json_encode([
                'errorCode'    => 4290000,
                'errorMessage' => 'Rate limit exceeded',
            ], JSON_THROW_ON_ERROR)));

        // We only care that the correct endpoint was called; the 400 throws an exception.
        $this->expectException(ValidationException::class);
        $this->validator->getAppTransactionInfo();
    }

    /**
     * @covers \ReceiptValidator\AppleAppStore\Validator::getAppTransactionInfo
     */
    public function testGetAppTransactionInfoThrowsWhenSignedTransactionInfoMissing(): void
    {
        $this->mockClient
            ->method('sendRequest')
            ->willReturn(new GuzzleResponse(200, [], json_encode([], JSON_THROW_ON_ERROR)));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing or invalid signedTransactionInfo');
        $this->validator->getAppTransactionInfo();
    }

    // -------------------------------------------------------------------------
    // AppTransaction parsing
    // -------------------------------------------------------------------------

    #[DataProvider('appTransactionProvider')]
    public function testAppTransactionParsing(array $raw, array $expected): void
    {
        $tx = new AppTransaction($raw);

        self::assertSame($expected['appTransactionId'],           $tx->getAppTransactionId());
        self::assertSame($expected['appAppleId'],                 $tx->getAppAppleId());
        self::assertSame($expected['bundleId'],                   $tx->getBundleId());
        self::assertSame($expected['applicationVersion'],         $tx->getApplicationVersion());
        self::assertSame($expected['versionExternalIdentifier'],  $tx->getVersionExternalIdentifier());
        self::assertSame($expected['originalApplicationVersion'], $tx->getOriginalApplicationVersion());
        self::assertSame($expected['originalPlatform'],           $tx->getOriginalPlatform());
        self::assertSame($expected['receiptType'],                $tx->getReceiptType());
        self::assertSame($expected['deviceVerification'],         $tx->getDeviceVerification());
        self::assertSame($expected['deviceVerificationNonce'],    $tx->getDeviceVerificationNonce());
        self::assertSame($expected['environment'],                $tx->getEnvironment());
    }

    public static function appTransactionProvider(): array
    {
        return [
            'full payload' => [
                'raw' => [
                    'appTransactionId'           => 'uuid-1234-abcd',
                    'appAppleId'                 => 987654321,
                    'bundleId'                   => 'com.example.app',
                    'applicationVersion'         => '2.3.1',
                    'versionExternalIdentifier'  => 840,
                    'originalApplicationVersion' => '1.0.0',
                    'originalPlatform'           => 'iOS',
                    'receiptType'                => 'ProductionSandbox',
                    'deviceVerification'         => 'base64encodedvalue==',
                    'deviceVerificationNonce'    => 'nonce-uuid-5678',
                    'environment'                => 'Sandbox',
                    'receiptCreationDate'        => 1700000000000,
                    'originalPurchaseDate'       => 1680000000000,
                    'signedDate'                 => 1710000000000,
                ],
                'expected' => [
                    'appTransactionId'           => 'uuid-1234-abcd',
                    'appAppleId'                 => 987654321,
                    'bundleId'                   => 'com.example.app',
                    'applicationVersion'         => '2.3.1',
                    'versionExternalIdentifier'  => 840,
                    'originalApplicationVersion' => '1.0.0',
                    'originalPlatform'           => 'iOS',
                    'receiptType'                => 'ProductionSandbox',
                    'deviceVerification'         => 'base64encodedvalue==',
                    'deviceVerificationNonce'    => 'nonce-uuid-5678',
                    'environment'                => Environment::SANDBOX,
                ],
            ],
            'empty payload' => [
                'raw'      => [],
                'expected' => [
                    'appTransactionId'           => null,
                    'appAppleId'                 => null,
                    'bundleId'                   => null,
                    'applicationVersion'         => null,
                    'versionExternalIdentifier'  => null,
                    'originalApplicationVersion' => null,
                    'originalPlatform'           => null,
                    'receiptType'                => null,
                    'deviceVerification'         => null,
                    'deviceVerificationNonce'    => null,
                    'environment'                => Environment::PRODUCTION,
                ],
            ],
        ];
    }

    public function testAppTransactionDatesParsedFromMilliseconds(): void
    {
        $tx = new AppTransaction([
            'receiptCreationDate'  => 1700000000000,
            'originalPurchaseDate' => 1680000000000,
            'preorderDate'         => 1670000000000,
            'signedDate'           => 1710000000000,
        ]);

        self::assertInstanceOf(CarbonImmutable::class, $tx->getReceiptCreationDate());
        self::assertInstanceOf(CarbonImmutable::class, $tx->getOriginalPurchaseDate());
        self::assertInstanceOf(CarbonImmutable::class, $tx->getPreorderDate());
        self::assertInstanceOf(CarbonImmutable::class, $tx->getSignedDate());
    }

    public function testPreorderDateIsNullWhenNotPresent(): void
    {
        $tx = new AppTransaction(['bundleId' => 'com.example']);
        self::assertNull($tx->getPreorderDate());
    }

    public function testProductionEnvironmentParsed(): void
    {
        $tx = new AppTransaction(['environment' => 'Production']);
        self::assertSame(Environment::PRODUCTION, $tx->getEnvironment());
    }
}
