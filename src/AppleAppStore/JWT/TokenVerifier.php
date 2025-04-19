<?php

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Token\Plain as Token;
use OpenSSLCertificate;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * App Store JWS Verifier.
 * Verifies a signed payload is signed by Apple.
 */
class TokenVerifier
{
    /**
     * Expected Apple root and intermediate certificate SHA-1 fingerprints.
     */
    private const array APPLE_CERTIFICATE_FINGERPRINTS = [
        '0be38bfe21fd434d8cc51cbe0e2bc7758ddbf97b',
        'b52cb02fd567e0359fe8fa4d4c41037970fe01b0',
    ];

    private const int EXPECTED_CHAIN_LENGTH = 3;
    private const int EXPECTED_JWT_SEGMENTS = 3;
    private const string EXPECTED_ALGORITHM = 'ES256';

    /**
     * Verifies the JWT token is signed by Apple using a valid x5c chain.
     *
     * @param Token $token
     * @return bool
     * @throws ValidationException
     */
    public function verify(Token $token): bool
    {
        $segments = explode('.', $token->toString());
        if (count($segments) !== self::EXPECTED_JWT_SEGMENTS) {
            throw new ValidationException('Invalid JWT format: expected 3 segments');
        }

        $headers = $token->headers()->all();

        if (($headers['alg'] ?? null) !== self::EXPECTED_ALGORITHM) {
            throw new ValidationException('Invalid algorithm: expected ES256');
        }

        if (!isset($headers['x5c']) || !is_array($headers['x5c']) || count($headers['x5c']) !== self::EXPECTED_CHAIN_LENGTH) {
            throw new ValidationException('Invalid x5c header: expected certificate chain with 3 entries');
        }

        $chain = $this->chain($headers['x5c']);

        if (count($chain) !== self::EXPECTED_CHAIN_LENGTH) {
            throw new ValidationException('Certificate chain parsing failed');
        }

        [$leaf, $intermediate, $root] = $chain;

        $fingerprints = [
            openssl_x509_fingerprint($intermediate),
            openssl_x509_fingerprint($root),
        ];

        if ($fingerprints !== self::APPLE_CERTIFICATE_FINGERPRINTS) {
            throw new ValidationException('Certificate fingerprints do not match known Apple fingerprints');
        }

        if (openssl_x509_verify($leaf, $intermediate) !== 1) {
            throw new ValidationException('Failed to verify leaf certificate against intermediate');
        }

        if (openssl_x509_verify($intermediate, $root) !== 1) {
            throw new ValidationException('Failed to verify intermediate certificate against root');
        }

        if (!openssl_x509_export($leaf, $exportedCert)) {
            throw new ValidationException('Unable to export leaf certificate');
        }

        try {
            $constraint = new SignedWith(new Sha256(), InMemory::plainText($exportedCert));
            $constraint->assert($token);
        } catch (ConstraintViolation $e) {
            throw new ValidationException('JWT signature verification failed', 0, $e);
        }

        return true;
    }

    /**
     * Builds an array of X.509 certificates from base64-encoded DER strings.
     *
     * @param array<string> $certificates
     * @return array<OpenSSLCertificate>
     * @throws ValidationException
     */
    private function chain(array $certificates): array
    {
        $result = [];

        foreach ($certificates as $cert) {
            $parsed = $this->base64DerToCert($cert);
            if ($parsed === false) {
                throw new ValidationException('Failed to parse X.509 certificate');
            }
            $result[] = $parsed;
        }

        return $result;
    }

    /**
     * Converts a base64-encoded DER certificate to an OpenSSL certificate resource.
     *
     * @param string $certificate
     * @return OpenSSLCertificate|false
     */
    private function base64DerToCert(string $certificate): false|OpenSSLCertificate
    {
        $contents = "-----BEGIN CERTIFICATE-----\n" .
            chunk_split($certificate, 64, "\n") .
            "-----END CERTIFICATE-----\n";

        return openssl_x509_read($contents);
    }
}
