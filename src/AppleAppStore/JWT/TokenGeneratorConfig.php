<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;

/**
 * Encapsulates the complete configuration for generating an App Store Connect API token.
 *
 * This immutable object holds the JWT library configuration, the token issuer details,
 * and a clock instance, providing a stable and reliable setup for the TokenGenerator.
 */
final readonly class TokenGeneratorConfig
{
    /**
     * The underlying JWT library configuration.
     */
    private Configuration $config;

    /**
     * The entity that issues the token (your developer account).
     */
    private TokenIssuer $issuer;

    /**
     * The clock used to determine the token's issuance and expiration times.
     */
    private Clock $clock;

    /**
     * Constructs the TokenGeneratorConfig.
     *
     * @param Configuration $config The JWT library configuration.
     * @param TokenIssuer $issuer The token issuer details.
     * @param Clock $clock The clock for timestamp generation.
     */
    public function __construct(Configuration $config, TokenIssuer $issuer, Clock $clock)
    {
        $this->config = $config;
        $this->issuer = $issuer;
        $this->clock = $clock;
    }

    /**
     * Creates a standard configuration for the App Store Server API.
     *
     * @param TokenIssuer $issuer The configured token issuer.
     * @param Clock|null $clock An optional clock instance, primarily for testing. Defaults to the system clock.
     * @return self A new configuration object.
     */
    public static function forAppStore(TokenIssuer $issuer, ?Clock $clock = null): self
    {
        $config = Configuration::forSymmetricSigner(
            $issuer->signer(),
            $issuer->key()
        );

        return new self($config, $issuer, $clock ?? SystemClock::fromSystemTimezone());
    }

    /**
     * Returns the JWT library configuration.
     */
    public function config(): Configuration
    {
        return $this->config;
    }

    /**
     * Returns the token issuer.
     */
    public function issuer(): TokenIssuer
    {
        return $this->issuer;
    }

    /**
     * Returns the clock instance.
     */
    public function clock(): Clock
    {
        return $this->clock;
    }
}
