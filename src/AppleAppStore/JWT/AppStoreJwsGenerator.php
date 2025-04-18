<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Token\Plain;
use ReceiptValidator\Exceptions\ValidationException;

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
     * @return Jws
     *
     */
    public function generate(): Jws
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

        if ($token instanceof Plain) {
            return Jws::fromJwtPlain($token);
        }

        throw new ValidationException('Invalid jwt token');
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
