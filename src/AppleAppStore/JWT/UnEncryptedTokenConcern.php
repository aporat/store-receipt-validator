<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use DateTimeInterface;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain as PlainToken;
use Lcobucci\JWT\Token\Signature;

/**
 * Trait UnEncryptedTokenConcern
 *
 * @property PlainToken $token
 */
trait UnEncryptedTokenConcern
{
    /**
     * Returns the token headers.
     */
    public function headers(): DataSet
    {
        return $this->token->headers();
    }

    /**
     * Returns if the token is allowed to be used by the audience.
     *
     * @param non-empty-string $audience
     */
    public function isPermittedFor(string $audience): bool
    {
        return $this->token->isPermittedFor($audience);
    }

    /**
     * Returns if the token has the given ID.
     *
     * @param non-empty-string $id
     */
    public function isIdentifiedBy(string $id): bool
    {
        return $this->token->isIdentifiedBy($id);
    }

    /**
     * Returns if the token has the given subject.
     *
     * @param non-empty-string $subject
     */
    public function isRelatedTo(string $subject): bool
    {
        return $this->token->isRelatedTo($subject);
    }

    /**
     * Returns if the token was issued by any of given issuers.
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function hasBeenIssuedBy(string ...$issuers): bool
    {
        return $this->token->hasBeenIssuedBy(...$issuers);
    }

    /**
     * Returns if the token was issued before the given time.
     */
    public function hasBeenIssuedBefore(DateTimeInterface $now): bool
    {
        return $this->token->hasBeenIssuedBefore($now);
    }

    /**
     * Returns if the token minimum time is before the given time.
     */
    public function isMinimumTimeBefore(DateTimeInterface $now): bool
    {
        return $this->token->isMinimumTimeBefore($now);
    }

    /**
     * Returns if the token is expired.
     */
    public function isExpired(DateTimeInterface $now): bool
    {
        return $this->token->isExpired($now);
    }

    /**
     * Returns an encoded representation of the token.
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function toString(): string
    {
        return $this->token->toString();
    }

    /**
     * Returns the token claims.
     */
    public function claims(): DataSet
    {
        return $this->token->claims();
    }

    /**
     * Returns the token signature.
     */
    public function signature(): Signature
    {
        return $this->token->signature();
    }

    /**
     * Returns the token payload.
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function payload(): string
    {
        return $this->token->payload();
    }
}
