<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser as JwtParser;
use Lcobucci\JWT\Token\Plain as Token;
use ReceiptValidator\Exceptions\ValidationException;
use Throwable;

/**
 * Generates and decodes ES256 JSON Web Tokens for the App Store Server API.
 *
 * This utility class handles the creation of signed tokens required for API
 * authentication and the parsing of signed data from Apple's responses.
 */
final class TokenGenerator
{
    /**
     * The audience claim required for App Store Connect API tokens.
     */
    public const string AUDIENCE = 'appstoreconnect-v1';

    /**
     * The default expiration time for generated tokens, in minutes.
     */
    public const int EXPIRATION_MINUTES = 60;

    /**
     * The configuration for generating the token.
     */
    private readonly TokenGeneratorConfig $config;

    /**
     * Constructs the TokenGenerator.
     *
     * @param TokenGeneratorConfig $config The configuration object containing the issuer, key, and signer.
     */
    public function __construct(TokenGeneratorConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generates a new signed JSON Web Token.
     *
     * @return Token The generated token object.
     * @throws ValidationException If the configuration is invalid or token generation fails.
     */
    public function generate(): Token
    {
        $issuer = $this->config->issuer();
        if (empty($issuer->id())) {
            throw new ValidationException('Issuer ID cannot be empty.');
        }

        try {
            $clock = $this->config->clock();
            $now = $clock->now();

            $token = $this->config->config()->builder()
                ->withHeader('kid', $issuer->key()->kid())
                ->issuedBy($issuer->id())
                ->issuedAt($now)
                ->expiresAt($now->modify('+' . self::EXPIRATION_MINUTES . ' minutes'))
                ->permittedFor(self::AUDIENCE)
                ->withClaim('bid', $issuer->bundle())
                ->getToken($issuer->signer(), $issuer->key());

            if (!$token instanceof Token) {
                throw new ValidationException('The generated token is not a valid JWT instance.');
            }

            return $token;
        } catch (Throwable $e) {
            throw new ValidationException('Failed to generate JWT: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Decodes a JWS string into a token object without signature verification.
     *
     * @param string $signedPayload The JWS string to decode.
     * @return Token The parsed, unverified token object.
     * @throws ValidationException If the payload is empty or cannot be parsed.
     */
    public static function decodeToken(string $signedPayload): Token
    {
        if (empty($signedPayload)) {
            throw new ValidationException('Cannot parse an empty JWS payload.');
        }

        try {
            $parser = new JwtParser(new JoseEncoder());
            $token = $parser->parse($signedPayload);

            if (!$token instanceof Token) {
                throw new ValidationException('The decoded payload is not a valid JWT instance.');
            }

            return $token;
        } catch (Throwable $e) {
            throw new ValidationException('Failed to decode JWS payload: ' . $e->getMessage(), 0, $e);
        }
    }
}
