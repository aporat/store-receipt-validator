<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer;

/**
 * Represents the issuer of an App Store Connect API token.
 *
 * This immutable data object encapsulates all the necessary components for
 * identifying the token's issuer: the issuer ID, the app's bundle ID,
 * the signing key, and the signing algorithm.
 */
final readonly class TokenIssuer
{
    /**
     * The issuer ID from your App Store Connect account.
     */
    private string $id;

    /**
     * The bundle identifier of your app.
     */
    private string $bundle;

    /**
     * The key used to sign the token.
     */
    private TokenKey $key;

    /**
     * The signing algorithm.
     */
    private Signer $signer;

    /**
     * Constructs the TokenIssuer.
     *
     * @param string $id Your issuer ID.
     * @param string $bundle The bundle identifier of your app.
     * @param TokenKey $key The signing key object.
     * @param Signer $signer The signing algorithm instance.
     */
    public function __construct(string $id, string $bundle, TokenKey $key, Signer $signer)
    {
        $this->id = $id;
        $this->bundle = $bundle;
        $this->key = $key;
        $this->signer = $signer;
    }

    /**
     * Returns the issuer ID.
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Returns the app's bundle identifier.
     */
    public function bundle(): string
    {
        return $this->bundle;
    }

    /**
     * Returns the signing key object.
     */
    public function key(): TokenKey
    {
        return $this->key;
    }

    /**
     * Returns the signing algorithm instance.
     */
    public function signer(): Signer
    {
        return $this->signer;
    }
}
