<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;

/**
 * JWT Generator Configuration
 */
final class TokenGeneratorConfig
{
    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * @var TokenIssuer
     */
    private TokenIssuer $issuer;

    /**
     * @var Clock
     */
    private Clock $clock;

    /**
     * @param Configuration $config
     * @param TokenIssuer $issuer
     * @param Clock $clock
     */
    public function __construct(Configuration $config, TokenIssuer $issuer, Clock $clock)
    {
        $this->config = $config;
        $this->issuer = $issuer;
        $this->clock = $clock;
    }

    /**
     * Creates a TokenGeneratorConfig for App Store Connect API.
     *
     * @param TokenIssuer $issuer
     * @param Clock|null $clock
     * @return static
     */
    public static function forAppStore(TokenIssuer $issuer, ?Clock $clock = null): self
    {
        $config = Configuration::forSymmetricSigner(
            $issuer->signer(),
            $issuer->key()
        );

        $clock = $clock ?? SystemClock::fromSystemTimezone();

        return new self($config, $issuer, $clock);
    }

    /**
     * @return Configuration
     */
    public function config(): Configuration
    {
        return $this->config;
    }

    /**
     * @return TokenIssuer
     */
    public function issuer(): TokenIssuer
    {
        return $this->issuer;
    }

    /**
     * @return Clock
     */
    public function clock(): Clock
    {
        return $this->clock;
    }
}
