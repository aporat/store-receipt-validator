<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer\Key as JwtKey;

/**
 * Decorates a JWT key with a Key ID (`kid`).
 *
 * This immutable value object encapsulates a signing key from the underlying JWT
 * library and associates it with the Key ID required by the App Store Server API.
 * It fulfills the `Lcobucci\JWT\Signer\Key` interface by proxying calls to the
 * decorated key object.
 */
final readonly class TokenKey implements JwtKey
{
    /**
     * The Key ID from your App Store Connect account.
     */
    private string $kid;

    /**
     * The underlying key object from the JWT library.
     */
    private JwtKey $jwtKey;

    /**
     * Constructs the TokenKey.
     *
     * @param string $kid The Key ID.
     * @param JwtKey $jwtKey The underlying key implementation (e.g., `InMemory::plainText(...)`).
     */
    public function __construct(string $kid, JwtKey $jwtKey)
    {
        $this->kid = $kid;
        $this->jwtKey = $jwtKey;
    }

    /**
     * Returns the Key ID.
     */
    public function kid(): string
    {
        return $this->kid;
    }

    /**
     * Returns the contents of the key.
     */
    public function contents(): string
    {
        return $this->jwtKey->contents();
    }

    /**
     * Returns the passphrase for the key (if any).
     */
    public function passphrase(): string
    {
        return $this->jwtKey->passphrase();
    }
}
