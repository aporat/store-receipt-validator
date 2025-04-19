<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use OpenSSLCertificate;

/**
 * App Store JWS Verifier
 * Verifies a signed payload is signed by Apple
 */
class AppStoreJwsVerifier
{
    private const array APPLE_CERTIFICATE_FINGERPRINTS = [
        // Fingerprint of https://www.apple.com/certificateauthority/AppleWWDRCAG6.cer
        '0be38bfe21fd434d8cc51cbe0e2bc7758ddbf97b',
        // Fingerprint of https://www.apple.com/certificateauthority/AppleRootCA-G3.cer
        'b52cb02fd567e0359fe8fa4d4c41037970fe01b0',
    ];

    private const int EXPECTED_CHAIN_LENGTH = 3;
    private const int EXPECTED_JWT_SEGMENTS = 3;
    private const string EXPECTED_ALGORITHM = 'ES256';

    /**
     * Verifies the JWS
     *
     * @param Jws $token
     * @return bool
     */
    public function verify(Jws $token): bool
    {

        $segments = explode('.', $token);
        if (count($segments) !== self::EXPECTED_JWT_SEGMENTS) {
            return false;
        }

        $headers = $token->headers()->all();

        if (($headers['alg'] ?? null) !== self::EXPECTED_ALGORITHM) {
            return false;
        }

        if (count($headers['x5c']) !== self::EXPECTED_CHAIN_LENGTH) {
            return false;
        }

        $chain = $this->chain($headers['x5c']);

        [$leaf, $intermediate, $root] = $chain;
        $fingerPrints = [
            openssl_x509_fingerprint($intermediate),
            openssl_x509_fingerprint($root),
        ];

        if (self::APPLE_CERTIFICATE_FINGERPRINTS !== $fingerPrints) {
            return false;
        }

        if (openssl_x509_verify($leaf, $intermediate) !== 1) {
            return false;
        }

        if (openssl_x509_verify($intermediate, $root) !== 1) {
            return false;
        }

        openssl_x509_export($chain[0], $exportedCertificate);
        (new SignedWith(new Sha256(), InMemory::plainText($exportedCertificate)))->assert($token);

        return true;
    }

    /**
     * @param array<string> $certificates
     * @return array<OpenSSLCertificate>
     */
    private function chain(array $certificates): array
    {
        $chain = [];

        foreach ($certificates as $certificate) {
            $chain[] = $this->base64DerToCert($certificate);
        }

        return $chain;
    }

    /**
     * Converts base64 DER string to x509 cert resource
     *
     * @param string $certificate
     * @return false|OpenSSLCertificate
     */
    private function base64DerToCert(string $certificate): false|OpenSSLCertificate
    {
        $contents =
            "-----BEGIN CERTIFICATE-----\n" .
            chunk_split($certificate, 64, "\n") .
            "-----END CERTIFICATE-----\n";

        return openssl_x509_read($contents);
    }
}
