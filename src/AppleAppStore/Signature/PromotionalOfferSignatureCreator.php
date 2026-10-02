<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\Signature;

use InvalidArgumentException;
use OpenSSLAsymmetricKey;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Creates signatures for promotional offers in the Original API for In-App Purchase (StoreKit 1).
 *
 * For StoreKit 2 promotional offers, use {@see PromotionalOfferV2SignatureCreator}.
 *
 * @see https://developer.apple.com/documentation/storekit/generating-a-signature-for-promotional-offers
 */
final class PromotionalOfferSignatureCreator
{
    /** Apple joins the payload fields with U+2063 INVISIBLE SEPARATOR. */
    private const string SEPARATOR = "\u{2063}";

    private readonly OpenSSLAsymmetricKey $signingKey;

    /**
     * @param string $signingKey Your private key (.p8 contents) downloaded from App Store Connect.
     * @param string $keyId      Your private key ID from App Store Connect.
     * @param string $bundleId   Your app's bundle ID.
     */
    public function __construct(
        string $signingKey,
        private readonly string $keyId,
        private readonly string $bundleId,
    ) {
        if ($signingKey === '' || $keyId === '' || $bundleId === '') {
            throw new InvalidArgumentException('Signing key, key ID and bundle ID must not be empty.');
        }

        $key = openssl_pkey_get_private($signingKey);
        if ($key === false) {
            throw new InvalidArgumentException('Unable to load the signing key: ' . (openssl_error_string() ?: 'invalid key'));
        }

        $this->signingKey = $key;
    }

    /**
     * Create a promotional offer signature.
     *
     * @param string $productIdentifier    The subscription product identifier.
     * @param string $subscriptionOfferId  The subscription discount identifier.
     * @param string $appAccountToken      An optional string value that you define; may be an empty string.
     * @param string $nonce                A one-time UUID your server generates. Generate a new nonce for every signature.
     * @param int    $timestamp            UNIX time in milliseconds. The timestamp keeps the offer active for 24 hours.
     *
     * @return string The Base64 encoded signature.
     *
     * @throws ValidationException
     */
    public function createSignature(
        string $productIdentifier,
        string $subscriptionOfferId,
        string $appAccountToken,
        string $nonce,
        int $timestamp,
    ): string {
        $payload = implode(self::SEPARATOR, [
            $this->bundleId,
            $this->keyId,
            $productIdentifier,
            $subscriptionOfferId,
            strtolower($appAccountToken),
            strtolower($nonce),
            (string) $timestamp,
        ]);

        $signature = '';
        if (!openssl_sign($payload, $signature, $this->signingKey, OPENSSL_ALGO_SHA256)) {
            throw new ValidationException('Failed to sign promotional offer: ' . (openssl_error_string() ?: 'unknown error'));
        }

        return base64_encode($signature);
    }
}
