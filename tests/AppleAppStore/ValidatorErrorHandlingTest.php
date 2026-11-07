<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\AppleAppStore\AppTransaction;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\AppleAppStore\Validator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Tests\AppleAppStore\Support\JwsSigning;
use RuntimeException;

/**
 * Transport failures, malformed responses and the signed-payload paths of the
 * App Store Server API client.
 *
 * @group apple-app-store
 */
#[CoversClass(Validator::class)]
final class ValidatorErrorHandlingTest extends TestCase
{
    use JwsSigning;

    private function validator(?string $signingKey = null): Validator
    {
        return new Validator(
            signingKey: $signingKey ?? (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8'),
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::SANDBOX,
        );
    }

    private function clientReturning(GuzzleResponse $response): ClientInterface
    {
        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return $client;
    }

    // -------------------------------------------------------------------------
    // Transport and token generation
    // -------------------------------------------------------------------------

    public function testConnectionFailureIsWrappedInValidationException(): void
    {
        $failure = new class ('Connection refused') extends RuntimeException implements ClientExceptionInterface {
        };

        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willThrowException($failure);

        $validator = $this->validator()->setHttpClient($client);

        try {
            $validator->getTransactionInfo('2000000000000123');
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertStringContainsString('Unable to connect to App Store Server API', $e->getMessage());
            self::assertStringContainsString('Connection refused', $e->getMessage());
            self::assertSame($failure, $e->getPrevious());
        }
    }

    public function testEmptySigningKeyFailsBeforeAnyRequest(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $validator = $this->validator('')->setHttpClient($client);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Cannot generate a token without a signing key');
        $validator->requestTestNotification();
    }

    public function testUnusableSigningKeyFailsBeforeAnyRequest(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $validator = $this->validator('not a private key')->setHttpClient($client);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('JWT generation failed');
        $validator->requestTestNotification();
    }

    // -------------------------------------------------------------------------
    // Error responses without an Apple error code
    // -------------------------------------------------------------------------

    /**
     * @return iterable<string, array{0: int, 1: string, 2: string}>
     */
    public static function errorResponses(): iterable
    {
        yield 'plain-text body' => [500, 'Internal Server Error', 'App Store API error [500]: Internal Server Error'];
        yield 'empty body' => [503, '', 'App Store API error [503]: Unexpected error'];
        yield 'JSON with errorMessage only' => [
            500,
            '{"errorMessage":"Something broke"}',
            'App Store API error [500]: Something broke',
        ];
        yield 'JSON with unknown errorCode' => [
            500,
            '{"errorCode":1,"errorMessage":"Unknown code"}',
            'App Store API error [500]: Unknown code',
        ];
        yield 'JSON without a message' => [500, '{"foo":"bar"}', 'App Store API error [500]: {"foo":"bar"}'];
        yield 'invalid JSON on a 500' => [500, '{not json', 'App Store API error [500]: {not json'];
        yield 'known Apple error code' => [
            404,
            '{"errorCode":4040010,"errorMessage":"ignored"}',
            'App Store API error [4040010]',
        ];
    }

    #[DataProvider('errorResponses')]
    public function testErrorResponsesProduceDescriptiveExceptions(int $status, string $body, string $message): void
    {
        $validator = $this->validator()->setHttpClient($this->clientReturning(new GuzzleResponse($status, [], $body)));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        $validator->getTransactionInfo('2000000000000123');
    }

    public function testErrorCodeFromApiBecomesExceptionCode(): void
    {
        $validator = $this->validator()->setHttpClient($this->clientReturning(
            new GuzzleResponse(404, [], '{"errorCode":4040010,"errorMessage":"x"}')
        ));

        try {
            $validator->getTransactionInfo('2000000000000123');
            self::fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            self::assertSame(4040010, $e->getCode());
        }
    }

    // -------------------------------------------------------------------------
    // Success responses with unusable bodies
    // -------------------------------------------------------------------------

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function nonObjectBodies(): iterable
    {
        yield 'JSON string' => ['"just a string"'];
        yield 'JSON number' => ['123'];
        yield 'JSON null' => ['null'];
        yield 'invalid JSON' => ['{not json'];
    }

    #[DataProvider('nonObjectBodies')]
    public function testSuccessWithNonObjectBodyIsRejected(string $body): void
    {
        $validator = $this->validator()->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], $body)));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid response format from App Store Server API');
        $validator->getTransactionInfo('2000000000000123');
    }

    public function testRequestTestNotificationRequiresAToken(): void
    {
        $validator = $this->validator()->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], '{}')));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing testNotificationToken');
        $validator->requestTestNotification();
    }

    // -------------------------------------------------------------------------
    // Endpoints that return a signed payload
    // -------------------------------------------------------------------------

    public function testGetTransactionInfoDecodesAVerifiedTransaction(): void
    {
        $body = json_encode([
            'signedTransactionInfo' => self::signWithStoreKitChain(self::storeKitTransactionClaims()),
        ], JSON_THROW_ON_ERROR);

        $validator = $this->validator()
            ->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], $body)))
            ->setTokenVerifier(self::storeKitVerifier());

        $transaction = $validator->getTransactionInfo('2000000123456789');

        self::assertInstanceOf(Transaction::class, $transaction);
        self::assertSame('2000000123456789', $transaction->getTransactionId());
        self::assertSame('com.example.premium', $transaction->getProductId());
    }

    public function testGetTransactionInfoRejectsAnUntrustedChain(): void
    {
        $body = json_encode([
            'signedTransactionInfo' => self::signWithStoreKitChain(self::storeKitTransactionClaims()),
        ], JSON_THROW_ON_ERROR);

        $validator = $this->validator()
            ->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], $body)))
            ->setTokenVerifier(new TokenVerifier());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not rooted to a trusted Apple certificate');
        $validator->getTransactionInfo('2000000123456789');
    }

    public function testGetTransactionInfoRejectsNonStringPayload(): void
    {
        $validator = $this->validator()->setHttpClient($this->clientReturning(
            new GuzzleResponse(200, [], '{"signedTransactionInfo": 123}')
        ));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing or invalid signedTransactionInfo');
        $validator->getTransactionInfo('2000000123456789');
    }

    public function testGetAppTransactionInfoDecodesAVerifiedAppTransaction(): void
    {
        $body = json_encode([
            'signedAppTransactionInfo' => self::signWithStoreKitChain([
                'bundleId'                   => 'com.example',
                'receiptType'                => 'Sandbox',
                'appAppleId'                 => 1234567890,
                'applicationVersion'         => '2.0',
                'originalApplicationVersion' => '1.0',
                'originalPurchaseDate'       => time() * 1000,
                'receiptCreationDate'        => time() * 1000,
                'signedDate'                 => time() * 1000,
            ]),
        ], JSON_THROW_ON_ERROR);

        $validator = $this->validator()
            ->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], $body)))
            ->setTokenVerifier(self::storeKitVerifier());

        $appTransaction = $validator->getAppTransactionInfo('2000000123456789');

        self::assertInstanceOf(AppTransaction::class, $appTransaction);
        self::assertSame('com.example', $appTransaction->getBundleId());
        self::assertSame('2.0', $appTransaction->getApplicationVersion());
    }

    public function testGetAppTransactionInfoRejectsAnUntrustedChain(): void
    {
        $body = json_encode([
            'signedAppTransactionInfo' => self::signWithStoreKitChain([
                'bundleId'    => 'com.example',
                'receiptType' => 'Sandbox',
                'signedDate'  => time() * 1000,
            ]),
        ], JSON_THROW_ON_ERROR);

        $validator = $this->validator()
            ->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], $body)))
            ->setTokenVerifier(new TokenVerifier());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not rooted to a trusted Apple certificate');
        $validator->getAppTransactionInfo('2000000123456789');
    }

    public function testGetAppTransactionInfoRequiresAPayload(): void
    {
        $validator = $this->validator()->setHttpClient($this->clientReturning(new GuzzleResponse(200, [], '{}')));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing or invalid signedAppTransactionInfo');
        $validator->getAppTransactionInfo('2000000123456789');
    }

    // -------------------------------------------------------------------------
    // Offline verification: environment claim edge cases
    // -------------------------------------------------------------------------

    public function testUnknownEnvironmentStringIsAMismatch(): void
    {
        $validator = $this->validator()->setTokenVerifier(self::storeKitVerifier());
        $jws       = self::signWithStoreKitChain(['environment' => 'Staging'] + self::storeKitTransactionClaims());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('expected "sandbox", got "Staging"');
        $validator->verifySignedTransaction($jws);
    }

    public function testNonStringEnvironmentIsAMismatch(): void
    {
        $validator = $this->validator()->setTokenVerifier(self::storeKitVerifier());
        $jws       = self::signWithStoreKitChain(['environment' => 42] + self::storeKitTransactionClaims());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('expected "sandbox", got "42"');
        $validator->verifySignedTransaction($jws);
    }

    public function testEnvironmentAliasProdMatchesProduction(): void
    {
        $validator = new Validator(
            signingKey: (string) file_get_contents(__DIR__ . '/certs/testSigningKey.p8'),
            keyId: 'ABC123XYZ',
            issuerId: 'DEF456UVW',
            bundleId: 'com.example',
            environment: Environment::PRODUCTION,
        );
        $validator->setTokenVerifier(self::storeKitVerifier());

        $renewal = $validator->verifySignedRenewalInfo(
            self::signWithStoreKitChain(['environment' => 'prod'] + self::storeKitRenewalClaims())
        );

        self::assertSame('2000000000000001', $renewal->getOriginalTransactionId());
    }
}
