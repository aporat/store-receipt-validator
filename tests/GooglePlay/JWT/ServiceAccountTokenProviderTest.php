<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay\JWT;

use DateTimeImmutable;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator as JwtValidator;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountCredentials;
use ReceiptValidator\GooglePlay\JWT\ServiceAccountTokenProvider;
use RuntimeException;

#[CoversClass(ServiceAccountTokenProvider::class)]
final class ServiceAccountTokenProviderTest extends TestCase
{
    private const string KEY_FILE = __DIR__ . '/../certs/testServiceAccount.json';
    private const string PEM_FILE = __DIR__ . '/../certs/testServiceAccountKey.pem';

    private ServiceAccountCredentials $credentials;

    protected function setUp(): void
    {
        $this->credentials = ServiceAccountCredentials::fromJson((string) file_get_contents(self::KEY_FILE));
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function newProvider(ClientInterface $client, ?FrozenClock $clock = null): ServiceAccountTokenProvider
    {
        $factory = new HttpFactory();

        return new ServiceAccountTokenProvider($this->credentials, $client, $factory, $factory, $clock);
    }

    public function testAssertionCarriesExpectedClaimsAndSignature(): void
    {
        $now      = new DateTimeImmutable('2026-09-05T12:00:00Z');
        $provider = $this->newProvider(Mockery::mock(ClientInterface::class), new FrozenClock($now));

        $token = (new Parser(new JoseEncoder()))->parse($provider->createAssertion());
        self::assertInstanceOf(Plain::class, $token);

        self::assertSame('RS256', $token->headers()->get('alg'));
        self::assertSame('abcdef1234567890', $token->headers()->get('kid'));
        self::assertSame('iap-verifier@test-project.iam.gserviceaccount.com', $token->claims()->get('iss'));
        self::assertSame(['https://oauth2.googleapis.com/token'], $token->claims()->get('aud'));
        self::assertSame(ServiceAccountTokenProvider::SCOPE_ANDROID_PUBLISHER, $token->claims()->get('scope'));
        self::assertEquals($now, $token->claims()->get('iat'));
        self::assertEquals($now->modify('+3600 seconds'), $token->claims()->get('exp'));

        // Signature verifies with the public half of the test key.
        $publicKey = openssl_pkey_get_details((\openssl_pkey_get_private((string) file_get_contents(self::PEM_FILE)) ?: throw new RuntimeException()))['key'];
        self::assertTrue(
            (new JwtValidator())->validate($token, new SignedWith(new Sha256(), InMemory::plainText($publicKey)))
        );
    }

    public function testAssertionOmitsKidWhenKeyIdMissing(): void
    {
        $this->credentials = new ServiceAccountCredentials(
            clientEmail: $this->credentials->clientEmail,
            privateKey: $this->credentials->privateKey,
        );

        $provider = $this->newProvider(Mockery::mock(ClientInterface::class));
        $token    = (new Parser(new JoseEncoder()))->parse($provider->createAssertion());

        self::assertFalse($token->headers()->has('kid'));
    }

    public function testAssertionFailsWithUnusableKey(): void
    {
        $this->credentials = new ServiceAccountCredentials(clientEmail: 'svc@example.com', privateKey: 'not a pem');
        $provider          = $this->newProvider(Mockery::mock(ClientInterface::class));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Failed to sign service account assertion');

        $provider->createAssertion();
    }

    public function testExchangesAssertionAndCachesToken(): void
    {
        $now   = new DateTimeImmutable('2026-09-05T12:00:00Z');
        $clock = new FrozenClock($now);

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')
            ->once()
            ->withArgs(function (RequestInterface $request): bool {
                parse_str((string) $request->getBody(), $form);

                return $request->getMethod() === 'POST'
                    && (string) $request->getUri() === 'https://oauth2.googleapis.com/token'
                    && $request->getHeaderLine('Content-Type') === 'application/x-www-form-urlencoded'
                    && ($form['grant_type'] ?? null) === ServiceAccountTokenProvider::GRANT_TYPE
                    && is_string($form['assertion'] ?? null)
                    && substr_count($form['assertion'], '.') === 2;
            })
            ->andReturn(new GuzzleResponse(200, [], (string) file_get_contents(__DIR__ . '/../fixtures/tokenResponse.json')));

        $provider = $this->newProvider($client, $clock);

        self::assertSame('ya29.test-access-token', $provider->getAccessToken());
        // Second call is served from cache; the mock allows exactly one request.
        self::assertSame('ya29.test-access-token', $provider->getAccessToken());
    }

    public function testRefreshesTokenOnceExpired(): void
    {
        $clock = new FrozenClock(new DateTimeImmutable('2026-09-05T12:00:00Z'));

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')
            ->twice()
            ->andReturn(
                new GuzzleResponse(200, [], '{"access_token":"first","expires_in":120}'),
                new GuzzleResponse(200, [], '{"access_token":"second","expires_in":3600}'),
            );

        $provider = $this->newProvider($client, $clock);

        self::assertSame('first', $provider->getAccessToken());

        // 120s lifetime minus the 60s skew: still valid at +59s, expired at +61s.
        $clock->setTo(new DateTimeImmutable('2026-09-05T12:00:59Z'));
        self::assertSame('first', $provider->getAccessToken());

        $clock->setTo(new DateTimeImmutable('2026-09-05T12:01:01Z'));
        self::assertSame('second', $provider->getAccessToken());
    }

    public function testClearCacheForcesNewExchange(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')
            ->twice()
            ->andReturn(
                new GuzzleResponse(200, [], '{"access_token":"first"}'),
                new GuzzleResponse(200, [], '{"access_token":"second"}'),
            );

        $provider = $this->newProvider($client);

        self::assertSame('first', $provider->getAccessToken());
        $provider->clearCache();
        self::assertSame('second', $provider->getAccessToken());
    }

    public function testOAuthErrorResponseThrows(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')
            ->once()
            ->andReturn(new GuzzleResponse(400, [], '{"error":"invalid_grant","error_description":"Invalid JWT Signature."}'));

        $provider = $this->newProvider($client);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Google OAuth token request failed [400] invalid_grant: Invalid JWT Signature.');
        $this->expectExceptionCode(400);

        $provider->getAccessToken();
    }

    public function testOAuthErrorWithoutBodyUsesDefaults(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andReturn(new GuzzleResponse(503, [], ''));

        $provider = $this->newProvider($client);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('[503] unknown_error: No description provided.');

        $provider->getAccessToken();
    }

    public function testMissingAccessTokenThrows(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andReturn(new GuzzleResponse(200, [], '{"token_type":"Bearer"}'));

        $provider = $this->newProvider($client);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('did not include an access_token');

        $provider->getAccessToken();
    }

    public function testTransportFailureIsWrapped(): void
    {
        $exception = new class ('connection refused') extends RuntimeException implements ClientExceptionInterface {
        };

        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('sendRequest')->once()->andThrow($exception);

        $provider = $this->newProvider($client);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unable to reach Google OAuth token endpoint - connection refused');

        $provider->getAccessToken();
    }
}
