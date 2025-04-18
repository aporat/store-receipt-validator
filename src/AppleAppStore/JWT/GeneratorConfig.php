<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\Clock\Clock;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;

/**
 * JWT Generator Configuration
 */
final class GeneratorConfig
{
    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * @var Issuer
     */
    private Issuer $issuer;

    /**
     * @var Clock
     */
    private Clock $clock;

    /**
     * @param Configuration $config
     * @param Issuer $issuer
     * @param Clock $clock
     */
    public function __construct(Configuration $config, Issuer $issuer, Clock $clock)
    {
        $this->config = $config;
        $this->issuer = $issuer;
        $this->clock = $clock;
    }

    /**
     * Creates a GeneratorConfig for App Store Connect API.
     *
     * @param Issuer $issuer
     * @param Clock|null $clock
     * @return static
     */
    public static function forAppStore(Issuer $issuer, ?Clock $clock = null): self
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
     * @return Issuer
     */
    public function issuer(): Issuer
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
