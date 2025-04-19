<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Plain as PlainToken;
use Lcobucci\JWT\UnencryptedToken;
use Stringable;

/**
 * Class Jws
 *
 * This is a wrapper class for PlainToken
 */
final class Jws implements Stringable, UnencryptedToken
{
    use UnEncryptedTokenConcern;

    /**
     * @var PlainToken
     */
    private PlainToken $token;

    /**
     * @param PlainToken $token
     */
    private function __construct(PlainToken $token)
    {
        $this->token = $token;
    }

    /**
     * Creates a new instance from a PlainToken instance
     *
     * @param PlainToken $token
     *
     * @return static
     */
    public static function fromJwtPlain(PlainToken $token): self
    {
        return new self($token);
    }

    /**
     * Get list of headers
     *
     * @return array<string, mixed>
     */
    public function getHeaders(): array
    {
        return $this->token->headers()->all();
    }

    /**
     * Get list of claims
     *
     * @return array<string, mixed>
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
