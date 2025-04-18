<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\UnencryptedToken;
use Stringable;

/**
 * Class Jws
 *
 * This is a wrapper class for Lcobucci\JWT\Token\Plain
 */
final class Jws implements Stringable, UnencryptedToken
{
    use UnEncryptedTokenConcern;

    /**
     * @var Token
     */
    private Token $token;

    /**
     * @param Plain $token
     */
    private function __construct(Token $token)
    {
        $this->token = $token;
    }

    /**
     * Creates a new instance from a Lcobucci\JWT\Token\Plain instance
     *
     * @param Token $token
     *
     * @return static
     */
    public static function fromJwtPlain(Token $token): self
    {
        return new self($token);
    }

    /**
     * Get list of headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->token->headers()->all();
    }

    /**
     * Get list of claims
     *
     * @return array
     */
    public function getClaims(): array
    {
        return $this->token->claims()->all();
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature(): string
    {
        return $this->token->signature()->toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->token->toString();
    }
}
