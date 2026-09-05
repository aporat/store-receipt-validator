<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\AbstractLogger;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\JWT\CallbackAccessTokenProvider;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountCredentials;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountTokenProvider;
use ReceiptValidator\GooglePlay\ProductPurchase;
use ReceiptValidator\GooglePlay\RevocationContext;
use ReceiptValidator\GooglePlay\SubscriptionPurchase;
use ReceiptValidator\GooglePlay\SubscriptionState;
use ReceiptValidator\GooglePlay\Validator;
use ReceiptValidator\GooglePlay\VoidedPurchasesParams;
use ReceiptValidator\GooglePlay\VoidedPurchasesResponse;
use ReceiptValidator\GooglePlay\VoidedPurchaseType;
use RuntimeException;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const string PACKAGE = 'app.example';
    private const string BASE    = 'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/app.example';
    private const string KEY_FILE = __DIR__ . '/certs/testServiceAccount.json';

    private function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__ . "/fixtures/{$name}.json");
    }

    /**
     * A validator with a static bearer token, so tests exercise only the API call.
     */
    private function newValidator(ClientInterface $client): Validator
    {
        $validator = new Validator(self::PACKAGE, environment: Environment::SANDBOX);
        $validator->setHttpClient($client);
        $validator->setAccessTokenProvider(new CallbackAccessTokenProvider(static fn (): string => 'static-token'));

        return $validator;
    }

    /**
     * @param callable(RequestInterface): bool $matcher
     */
    private function mockClient(callable $matcher, GuzzleResponse $response): ClientInterface&MockInterface
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->withArgs($matcher)->andReturn($response);

        return $client;
    }

    public function testConstructorParsesJsonCredentialsAndExposesPackageName(): void
    {
        $validator = new Validator(self::PACKAGE, $this->fixture('../certs/testServiceAccount'));

        self::assertSame(self::PACKAGE, $validator->getPackageName());
        self::assertSame(Environment::PRODUCTION, $validator->getEnvironment());
        self::assertInstanceOf(ServiceAccountTokenProvider::class, $validator->getAccessTokenProvider());
        // Same instance on repeated calls.
        self::assertSame($validator->getAccessTokenProvider(), $validator->getAccessTokenProvider());
    }

    public function testConstructorAcceptsCredentialsObject(): void
    {
        $credentials = ServiceAccountCredentials::fromJson((string) file_get_contents(self::KEY_FILE));
        $validator   = new Validator(self::PACKAGE, $credentials);

        self::assertInstanceOf(ServiceAccountTokenProvider::class, $validator->getAccessTokenProvider());
    }

    public function testConstructorRejectsEmptyPackageName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('package name cannot be empty');

        new Validator('');
    }

    public function testMissingCredentialsAndProviderThrows(): void
    {
        $validator = new Validator(self::PACKAGE);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('has no credentials');

        $validator->getSubscriptionPurchaseV2('token');
    }

    public function testEndToEndWithServiceAccountTokenExchange(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')
            ->twice()
            ->andReturnUsing(function (RequestInterface $request): GuzzleResponse {
                if ((string) $request->getUri() === 'https://oauth2.googleapis.com/token') {
                    return new GuzzleResponse(200, [], $this->fixture('tokenResponse'));
                }

                self::assertSame('Bearer ya29.test-access-token', $request->getHeaderLine('Authorization'));

                return new GuzzleResponse(200, [], $this->fixture('subscriptionPurchaseV2'));
            });

        $validator = new Validator(self::PACKAGE, $this->fixture('../certs/testServiceAccount'));
        $validator->setHttpClient($client);

        $purchase = $validator->getSubscriptionPurchaseV2('sub-token');

        self::assertSame(SubscriptionState::ACTIVE, $purchase->getSubscriptionState());
    }

    public function testGetSubscriptionPurchaseV2(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'GET'
                && (string) $r->getUri() === self::BASE . '/purchases/subscriptionsv2/tokens/sub%2Ftoken%3D'
                && $r->getHeaderLine('Authorization') === 'Bearer static-token'
                && $r->getHeaderLine('Accept') === 'application/json',
            new GuzzleResponse(200, [], $this->fixture('subscriptionPurchaseV2'))
        );

        $purchase = $this->newValidator($client)->getSubscriptionPurchaseV2('sub/token=');

        self::assertInstanceOf(SubscriptionPurchase::class, $purchase);
        self::assertSame('app.example.subscription', $purchase->getLatestLineItem()?->getProductId());
    }

    public function testValidateUsesPurchaseToken(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => str_ends_with((string) $r->getUri(), '/purchases/subscriptionsv2/tokens/fluent-token'),
            new GuzzleResponse(200, [], $this->fixture('subscriptionPurchaseV2'))
        );

        $purchase = $this->newValidator($client)->setPurchaseToken('fluent-token')->validate();

        self::assertSame(SubscriptionState::ACTIVE, $purchase->getSubscriptionState());
    }

    public function testValidateAcceptsTokenArgument(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => str_ends_with((string) $r->getUri(), '/purchases/subscriptionsv2/tokens/arg-token'),
            new GuzzleResponse(200, [], $this->fixture('subscriptionPurchaseV2'))
        );

        $this->newValidator($client)->validate('arg-token');
    }

    public function testValidateWithoutTokenThrows(): void
    {
        $validator = $this->newValidator(Mockery::mock(ClientInterface::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing purchase token');

        $validator->validate();
    }

    public function testEmptyPurchaseTokenThrowsBeforeRequest(): void
    {
        $validator = $this->newValidator(Mockery::mock(ClientInterface::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('purchase token cannot be empty');

        $validator->getSubscriptionPurchaseV2('');
    }

    public function testAcknowledgeSubscriptionSendsPayload(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'POST'
                && (string) $r->getUri() === self::BASE . '/purchases/subscriptions/app.example.subscription/tokens/tok:acknowledge'
                && $r->getHeaderLine('Content-Type') === 'application/json'
                && (string) $r->getBody() === '{"developerPayload":"hello"}',
            new GuzzleResponse(200, [], '')
        );

        $this->newValidator($client)->acknowledgeSubscription('app.example.subscription', 'tok', 'hello');
    }

    public function testAcknowledgeSubscriptionWithoutPayloadSendsEmptyObject(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => (string) $r->getBody() === '[]' || (string) $r->getBody() === '{}',
            new GuzzleResponse(204, [], '')
        );

        $this->newValidator($client)->acknowledgeSubscription('app.example.subscription', 'tok');
    }

    public function testAcknowledgeSubscriptionRequiresSubscriptionId(): void
    {
        $validator = $this->newValidator(Mockery::mock(ClientInterface::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('subscription ID cannot be empty');

        $validator->acknowledgeSubscription('', 'tok');
    }

    public function testRevokeSubscription(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'POST'
                && (string) $r->getUri() === self::BASE . '/purchases/subscriptionsv2/tokens/tok:revoke'
                && (string) $r->getBody() === '{"revocationContext":{"proratedRefund":{}}}',
            new GuzzleResponse(200, [], '{}')
        );

        $this->newValidator($client)->revokeSubscription('tok', RevocationContext::proratedRefund());
    }

    public function testGetProductPurchase(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'GET'
                && (string) $r->getUri() === self::BASE . '/purchases/products/app.example.coins.100/tokens/otp-token',
            new GuzzleResponse(200, [], $this->fixture('productPurchase'))
        );

        $purchase = $this->newValidator($client)->getProductPurchase('app.example.coins.100', 'otp-token');

        self::assertInstanceOf(ProductPurchase::class, $purchase);
        self::assertTrue($purchase->isPurchased());
        self::assertSame('otp-token', $purchase->getPurchaseToken());
    }

    public function testGetProductPurchaseRequiresProductId(): void
    {
        $validator = $this->newValidator(Mockery::mock(ClientInterface::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('product ID cannot be empty');

        $validator->getProductPurchase('', 'tok');
    }

    public function testAcknowledgeProduct(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'POST'
                && (string) $r->getUri() === self::BASE . '/purchases/products/sku/tokens/tok:acknowledge'
                && (string) $r->getBody() === '{"developerPayload":"p"}',
            new GuzzleResponse(200, [], '')
        );

        $this->newValidator($client)->acknowledgeProduct('sku', 'tok', 'p');
    }

    public function testConsumeProduct(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'POST'
                && (string) $r->getUri() === self::BASE . '/purchases/products/sku/tokens/tok:consume'
                && !$r->hasHeader('Content-Type'),
            new GuzzleResponse(200, [], '')
        );

        $this->newValidator($client)->consumeProduct('sku', 'tok');
    }

    public function testGetVoidedPurchasesWithParams(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => $r->getMethod() === 'GET'
                && (string) $r->getUri() === self::BASE . '/purchases/voidedpurchases?maxResults=10&type=1',
            new GuzzleResponse(200, [], $this->fixture('voidedPurchases'))
        );

        $response = $this->newValidator($client)->getVoidedPurchases(
            new VoidedPurchasesParams(maxResults: 10, type: VoidedPurchaseType::INCLUDE_SUBSCRIPTIONS)
        );

        self::assertInstanceOf(VoidedPurchasesResponse::class, $response);
        self::assertCount(2, $response->getVoidedPurchases());
        self::assertSame(Environment::SANDBOX, $response->getEnvironment());
    }

    public function testGetVoidedPurchasesWithoutParams(): void
    {
        $client = $this->mockClient(
            fn (RequestInterface $r): bool => (string) $r->getUri() === self::BASE . '/purchases/voidedpurchases',
            new GuzzleResponse(200, [], '{"voidedPurchases":[]}')
        );

        self::assertFalse($this->newValidator($client)->getVoidedPurchases()->hasMore());
    }

    public function testKnownApiErrorIsMappedToFriendlyMessage(): void
    {
        $client = $this->mockClient(
            fn (): bool => true,
            new GuzzleResponse(400, [], $this->fixture('errorResponse'))
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'Google Play API error [400 purchaseTokenDoesNotMatchPackageName]: The purchase token does not belong to this package name.'
        );
        $this->expectExceptionCode(400);

        $this->newValidator($client)->getSubscriptionPurchaseV2('tok');
    }

    public function testUnknownReasonUsesGoogleMessage(): void
    {
        $body   = '{"error":{"code":410,"message":"Token is gone.","errors":[{"reason":"somethingNew","message":"Detail"}]}}';
        $client = $this->mockClient(fn (): bool => true, new GuzzleResponse(410, [], $body));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Google Play API error [410 somethingNew]: Token is gone.');

        $this->newValidator($client)->getSubscriptionPurchaseV2('tok');
    }

    public function testErrorWithoutTopLevelMessageFallsBackToDetail(): void
    {
        $body   = '{"error":{"errors":[{"reason":"weird","message":"Detail only"}]}}';
        $client = $this->mockClient(fn (): bool => true, new GuzzleResponse(400, [], $body));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('[400 weird]: Detail only');

        $this->newValidator($client)->getSubscriptionPurchaseV2('tok');
    }

    public function testErrorWithoutJsonUsesStatusDefaults(): void
    {
        $validator = $this->newValidator($this->mockClient(fn (): bool => true, new GuzzleResponse(401, [], '')));

        try {
            $validator->getSubscriptionPurchaseV2('tok');
            self::fail('Expected exception');
        } catch (ValidationException $e) {
            self::assertSame('Google Play API error [401]: Unauthenticated', $e->getMessage());
            self::assertSame(401, $e->getCode());
        }

        foreach ([403 => 'Forbidden', 404 => 'Not Found', 410 => 'Gone'] as $status => $message) {
            Mockery::close();
            $validator = $this->newValidator($this->mockClient(fn (): bool => true, new GuzzleResponse($status, [], 'not json')));

            try {
                $validator->getSubscriptionPurchaseV2('tok');
                self::fail('Expected exception');
            } catch (ValidationException $e) {
                self::assertSame("Google Play API error [$status]: $message", $e->getMessage());
            }
        }

        Mockery::close();
        $validator = $this->newValidator($this->mockClient(fn (): bool => true, new GuzzleResponse(500, [], 'raw body')));

        try {
            $validator->getSubscriptionPurchaseV2('tok');
            self::fail('Expected exception');
        } catch (ValidationException $e) {
            self::assertSame('Google Play API error [500]: raw body', $e->getMessage());
        }

        Mockery::close();
        $validator = $this->newValidator($this->mockClient(fn (): bool => true, new GuzzleResponse(502, [], '')));

        try {
            $validator->getSubscriptionPurchaseV2('tok');
            self::fail('Expected exception');
        } catch (ValidationException $e) {
            self::assertSame('Google Play API error [502]: Unexpected error', $e->getMessage());
        }
    }

    public function testInvalidJsonOnSuccessThrows(): void
    {
        $client = $this->mockClient(fn (): bool => true, new GuzzleResponse(200, [], 'not json'));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid response format from Google Play API.');

        $this->newValidator($client)->getSubscriptionPurchaseV2('tok');
    }

    public function testTransportFailureIsWrapped(): void
    {
        $exception = new class ('timed out') extends RuntimeException implements ClientExceptionInterface {
        };

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andThrow($exception);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to connect to Google Play API - timed out');

        $this->newValidator($client)->getSubscriptionPurchaseV2('tok');
    }

    public function testLoggerReceivesRequestLifecycle(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var array<int, array{0: string, 1: string}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message];
            }
        };

        $client = $this->mockClient(fn (): bool => true, new GuzzleResponse(200, [], $this->fixture('subscriptionPurchaseV2')));

        $this->newValidator($client)->setLogger($logger)->getSubscriptionPurchaseV2('tok');

        self::assertSame([
            ['debug', 'Google Play API request'],
            ['info', 'Google Play API request successful'],
        ], $logger->records);
    }

    public function testEndpointIsIdenticalForBothEnvironments(): void
    {
        $sandbox    = new Validator(self::PACKAGE, environment: Environment::SANDBOX);
        $production = new Validator(self::PACKAGE, environment: Environment::PRODUCTION);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')
            ->twice()
            ->withArgs(fn (RequestInterface $r): bool => str_starts_with((string) $r->getUri(), Validator::ENDPOINT))
            ->andReturn(new GuzzleResponse(200, [], '{}'));

        foreach ([$sandbox, $production] as $validator) {
            $validator->setHttpClient($client);
            $validator->setAccessTokenProvider(new CallbackAccessTokenProvider(static fn (): string => 't'));
            $validator->getSubscriptionPurchaseV2('tok');
        }
    }
}
