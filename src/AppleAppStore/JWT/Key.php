<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer\Key as JwtKey;

/**
 * Key value object for signing JWTs for App Store Connect API.
 * - kid: Key ID
 * - contents: Key content
 * - passphrase: Key passphrase
 */
final class Key implements JwtKey
{
    /**
     * Key ID.
     *
     * @var string
     */
    private string $kid;

    /**
     * JWT key implementation.
     *
     * @var JwtKey
     */
    private JwtKey $jwtKey;

    /**
     * @param string $kid
     * @param JwtKey $jwtKey
     */
    public function __construct(string $kid, JwtKey $jwtKey)
    {
        $this->kid = $kid;
        $this->jwtKey = $jwtKey;
    }

    public function kid(): string
    {
        return $this->kid;
    }

    public function contents(): string
    {
        return $this->jwtKey->contents();
    }

    public function passphrase(): string
    {
        return $this->jwtKey->passphrase();
    }
}
