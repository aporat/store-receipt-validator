<?php

declare(strict_types=1);

namespace ReceiptValidator\GooglePlay\JWT;

use DateTimeImmutable;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Mints Android Publisher access tokens from a service account using the OAuth 2.0
 * JWT bearer grant (RFC 7523).
 *
 * A signed RS256 assertion is exchanged at Google's token endpoint for a short-lived
 * bearer token, which is cached in memory until shortly before it expires.
 *
 * @see https://developers.google.com/identity/protocols/oauth2/service-account#httprest
 */
final class ServiceAccountTokenProvider implements AccessTokenProvider
{
    /** OAuth scope granting access to the Android Publisher API. */
    public const string SCOPE_ANDROID_PUBLISHER = 'https://www.googleapis.com/auth/androidpublisher';

    /** Grant type for the JWT bearer flow. */
    public const string GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /** Lifetime of the signed assertion. Google rejects anything over one hour. */
    public const int ASSERTION_LIFETIME_SECONDS = 3600;

    /** Refresh the cached token this many seconds before Google says it expires. */
    private const int EXPIRY_SKEW_SECONDS = 60;

    private readonly Clock $clock;

    private ?string $accessToken = null;

    private ?DateTimeImmutable $expiresAt = null;

    public function __construct(
        private readonly ServiceAccountCredentials $credentials,
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        ?Clock $clock = null,
        private readonly string $scope = self::SCOPE_ANDROID_PUBLISHER,
    ) {
        $this->clock = $clock ?? SystemClock::fromUTC();
    }

    public function getAccessToken(): string
    {
        $now = $this->clock->now();

        if ($this->accessToken !== null && $this->expiresAt !== null && $this->expiresAt > $now) {
            return $this->accessToken;
        }

        [$this->accessToken, $this->expiresAt] = $this->exchange($this->createAssertion($now));

        return $this->accessToken;
    }

    /**
     * Discard the cached token so the next call performs a fresh exchange.
     */
    public function clearCache(): void
    {
        $this->accessToken = null;
        $this->expiresAt   = null;
    }

    /**
     * Build the signed JWT assertion presented to the token endpoint.
     *
     * @throws ValidationException
     */
    public function createAssertion(?DateTimeImmutable $now = null): string
    {
        $now ??= $this->clock->now();

        $privateKey  = $this->credentials->privateKey;
        $clientEmail = $this->credentials->clientEmail;
        $tokenUri    = $this->credentials->tokenUri;

        // ServiceAccountCredentials rejects empty values; this only informs static analysis.
        assert($privateKey !== '' && $clientEmail !== '' && $tokenUri !== '');

        try {
            $key    = InMemory::plainText($privateKey);
            $config = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);

            $builder = $config->builder(ChainedFormatter::withUnixTimestampDates());

            if ($this->credentials->privateKeyId !== null && $this->credentials->privateKeyId !== '') {
                $builder = $builder->withHeader('kid', $this->credentials->privateKeyId);
            }

            return $builder
                ->issuedBy($clientEmail)
                ->permittedFor($tokenUri)
                ->issuedAt($now)
                ->expiresAt($now->modify('+' . self::ASSERTION_LIFETIME_SECONDS . ' seconds'))
                ->withClaim('scope', $this->scope)
                ->getToken($config->signer(), $config->signingKey())
                ->toString();
        } catch (Throwable $e) {
            throw new ValidationException('Failed to sign service account assertion: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Exchange the assertion for an access token.
     *
     * @return array{0: string, 1: DateTimeImmutable} The token and the time it should be refreshed.
     *
     * @throws ValidationException
     */
    private function exchange(string $assertion): array
    {
        $body = http_build_query([
            'grant_type' => self::GRANT_TYPE,
            'assertion'  => $assertion,
        ], '', '&', PHP_QUERY_RFC3986);

        $request = $this->requestFactory
            ->createRequest('POST', $this->credentials->tokenUri)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new ValidationException('Unable to reach Google OAuth token endpoint - ' . $e->getMessage(), 0, $e);
        }

        $status  = $response->getStatusCode();
        $decoded = json_decode((string) $response->getBody(), true);
        $decoded = is_array($decoded) ? $decoded : [];

        if ($status !== 200) {
            $error       = (string) ($decoded['error'] ?? 'unknown_error');
            $description = (string) ($decoded['error_description'] ?? 'No description provided.');

            throw new ValidationException("Google OAuth token request failed [$status] $error: $description", $status);
        }

        $token = $decoded['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new ValidationException('Google OAuth token response did not include an access_token.');
        }

        $expiresIn = (int) ($decoded['expires_in'] ?? self::ASSERTION_LIFETIME_SECONDS);
        $ttl       = max(0, $expiresIn - self::EXPIRY_SKEW_SECONDS);

        return [$token, $this->clock->now()->modify("+{$ttl} seconds")];
    }
}
