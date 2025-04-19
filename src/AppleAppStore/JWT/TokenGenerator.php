<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser as JwtParser;
use Lcobucci\JWT\Token\Plain as Token;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Generates ES256 JWT token for App Store Connect API.
 */
class TokenGenerator
{
    public const string AUDIENCE = 'appstoreconnect-v1';
    public const int EXPIRATION_MINUTES = 60;

    /**
     * @var TokenGeneratorConfig
     */
    private TokenGeneratorConfig $config;

    /**
     * TokenGenerator constructor.
     *
     * @param TokenGeneratorConfig $config
     */
    public function __construct(TokenGeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate a JWT.
     *
     * @return Token
     *
     * @throws ValidationException
     */
    public function generate(): Token
    {
        $builder = $this->config->config()->builder();
        $issuer = $this->config->issuer();
        $clock = $this->config->clock();

        try {
            $issuedAt = $clock->now();
            $expiresAt = $issuedAt->modify('+' . self::EXPIRATION_MINUTES . ' minutes');

            $token = $builder
                ->withHeader('kid', $issuer->key()->kid())
                ->issuedBy($issuer->id())
                ->issuedAt($issuedAt)
                ->expiresAt($expiresAt)
                ->permittedFor(self::AUDIENCE)
                ->withClaim('bid', $issuer->bundle())
                ->getToken($issuer->signer(), $issuer->key());

            if (!$token instanceof Token) {
                throw new ValidationException('Generated token is not a valid JWT');
            }

            return $token;
        } catch (Throwable $e) {
            throw new ValidationException('Failed to generate JWT token: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Decode a JWT string to a token object.
     *
     * @param string $signedPayload
     * @return Token
     * @throws ValidationException
     */
    public static function decodeToken(string $signedPayload): Token
    {
        try {
            $parser = new JwtParser(new JoseEncoder());
            $token = $parser->parse($signedPayload);

            if (!$token instanceof Token) {
                throw new ValidationException('Decoded token is not a valid JWT');
            }

            return $token;
        } catch (Throwable $e) {
            throw new ValidationException('Failed to decode JWT token: ' . $e->getMessage(), 0, $e);
        }
    }
}
