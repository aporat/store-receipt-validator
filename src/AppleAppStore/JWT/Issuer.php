<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer;

/**
 * Represents the issuer of the JWT token.
 * - iss: Developer ID
 * - alg: Signer algorithm
 * - key: Private key
 */
final class Issuer
{
    /**
     * Developer ID (issuer).
     *
     * @var string
     */
    private string $id;

    /**
     * App bundle ID.
     *
     * @var string
     */
    private string $bundle;

    /**
     * JWT signing key.
     *
     * @var Key
     */
    private Key $key;

    /**
     * JWT signer.
     *
     * @var Signer
     */
    private Signer $signer;

    /**
     * @param string $id
     * @param string $bundle
     * @param Key $key
     * @param Signer $signer
     */
    public function __construct(string $id, string $bundle, Key $key, Signer $signer)
    {
        $this->id = $id;
        $this->bundle = $bundle;
        $this->key = $key;
        $this->signer = $signer;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function bundle(): string
    {
        return $this->bundle;
    }

    public function key(): Key
    {
        return $this->key;
    }

    public function signer(): Signer
    {
        return $this->signer;
    }
}
