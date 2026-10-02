<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Signature;

use InvalidArgumentException;
use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Base class for the JWS signatures StoreKit requires to authorize App Store features.
 *
 * The JWS header uses ES256 with your key ID. The payload carries the base claims
 * Apple documents (iss, iat, aud, bid, nonce) plus feature-specific claims supplied
 * by the subclass. No exp claim is included; Apple derives expiry from iat.
 *
 * @see https://developer.apple.com/documentation/storekit/generating-jws-to-sign-app-store-requests
 */
abstract class JwsSignatureCreator
{
    private readonly Configuration $config;
    private readonly Clock $clock;

    /** @var non-empty-string */
    private readonly string $audience;

    /** @var non-empty-string */
    private readonly string $keyId;

    /** @var non-empty-string */
    private readonly string $issuerId;

    /** @var non-empty-string */
    private readonly string $bundleId;

    /**
     * @param string $audience   The aud claim for the feature being signed.
     * @param string $signingKey Your private key (.p8 contents) downloaded from App Store Connect.
     * @param string $keyId      Your private key ID from App Store Connect.
     * @param string $issuerId   Your issuer ID from the Keys page in App Store Connect.
     * @param string $bundleId   Your app's bundle ID.
     * @param Clock|null $clock  Clock used for the iat claim; defaults to the system clock.
     */
    public function __construct(
        string $audience,
        string $signingKey,
        string $keyId,
        string $issuerId,
        string $bundleId,
        ?Clock $clock = null,
    ) {
        if ($audience === '' || $signingKey === '' || $keyId === '' || $issuerId === '' || $bundleId === '') {
            throw new InvalidArgumentException('Signing key, key ID, issuer ID and bundle ID must not be empty.');
        }

        $this->audience = $audience;
        $this->keyId    = $keyId;
        $this->issuerId = $issuerId;
        $this->bundleId = $bundleId;

        $key          = InMemory::plainText($signingKey);
        $this->config = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);
        $this->clock  = $clock ?? SystemClock::fromSystemTimezone();
    }

    /**
     * Sign the base claims together with the given feature-specific claims.
     *
     * @param array<non-empty-string, mixed> $featureSpecificClaims
     *
     * @return string The JWS compact serialization.
     *
     * @throws ValidationException
     */
    protected function createJws(array $featureSpecificClaims): string
    {
        try {
            $builder = $this->config->builder()
                ->withHeader('kid', $this->keyId)
                ->issuedBy($this->issuerId)
                ->issuedAt($this->clock->now())
                ->permittedFor($this->audience)
                ->withClaim('bid', $this->bundleId)
                ->withClaim('nonce', self::uuidV4());

            foreach ($featureSpecificClaims as $name => $value) {
                $builder = $builder->withClaim($name, $value);
            }

            return $builder
                ->getToken($this->config->signer(), $this->config->signingKey())
                ->toString();
        } catch (Throwable $e) {
            throw new ValidationException('Failed to create JWS signature: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate a random RFC 4122 version 4 UUID for the one-time nonce claim.
     */
    private static function uuidV4(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
