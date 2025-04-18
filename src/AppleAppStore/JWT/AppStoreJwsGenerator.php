<?php

namespace ReceiptValidator\AppleAppStore\JWT;

/**
 * Generates ES256 JWT token for App Store Connect API
 */
class AppStoreJwsGenerator
{
    public const string AUDIENCE = 'appstoreconnect-v1';

    /**
     * @var GeneratorConfig
     */
    private GeneratorConfig $config;

    /**
     * @param GeneratorConfig $config
     */
    public function __construct(GeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate a JWT
     *
     * @param array $claims
     * @param array $headers
     * @return Jws
     *
     */
    public function generate(array $claims = [], array $headers = []): Jws
    {
        $builder = $this->config->config()->builder();
        $issuer = $this->config->issuer();
        $clock = $this->config->clock();

        $token = $builder
            ->withHeader('kid', $issuer->key()->kid())
            ->issuedBy($issuer->id())
            ->issuedAt($clock->now())
            ->expiresAt($clock->now()->modify('+1 hour'))
            ->permittedFor(self::AUDIENCE)
            ->withClaim('bid', $issuer->bundle())
            ->getToken($issuer->signer(), $issuer->key());

        return Jws::fromJwtPlain($token);
    }

    /**
     * @return GeneratorConfig
     */
    public function getConfig(): GeneratorConfig
    {
        return $this->config;
    }

    /**
     * @param GeneratorConfig $config
     * @return AppStoreJwsGenerator
     */
    public function setConfig(GeneratorConfig $config): AppStoreJwsGenerator
    {
        $this->config = $config;
        return $this;
    }
}
