<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore\JWT;

use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\ConstraintViolation;
use Lcobucci\JWT\Token\Plain as Token;
use OpenSSLCertificate;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * Verifies that a JWS token was signed by Apple using its public key infrastructure.
 *
 * This class validates the token's algorithm, extracts the X.509 certificate
 * chain from the header, verifies that the chain is rooted to a trusted Apple
 * certificate, and finally, verifies the token's signature using the public
 * key from the leaf certificate.
 */
final class TokenVerifier
{
    /**
     * The SHA-1 fingerprints of the trusted Apple Root and Intermediate certificates.
     */
    private const array APPLE_CERTIFICATE_FINGERPRINTS = [
        '0be38bfe21fd434d8cc51cbe0e2bc7758ddbf97b', // Apple Worldwide Developer Relations Certification Authority
        'b52cb02fd567e0359fe8fa4d4c41037970fe01b0', // Apple Inc. Root Certificate
    ];

    private const int EXPECTED_CHAIN_LENGTH = 3;
    private const string EXPECTED_ALGORITHM = 'ES256';

    /**
     * Verifies the integrity and authenticity of the JWS token.
     *
     * @param Token $token The parsed JWS token.
     * @return bool True if the token is valid and signed by Apple.
     * @throws ValidationException If any verification step fails.
     */
    public function verify(Token $token): bool
    {
        $this->validateHeaders($token);

        $chain = $this->extractAndParseCertificateChain($token);
        $this->verifyCertificateChain($chain);

        $this->assertSignature($token, $chain[0]);

        return true;
    }

    /**
     * Validates the critical headers of the JWS token.
     *
     * @param Token $token
     * @throws ValidationException
     */
    private function validateHeaders(Token $token): void
    {
        $headers = $token->headers()->all();

        if (($headers['alg'] ?? null) !== self::EXPECTED_ALGORITHM) {
            throw new ValidationException('Token algorithm must be ES256.');
        }

        if (!isset($headers['x5c']) || !is_array($headers['x5c']) || count($headers['x5c']) < self::EXPECTED_CHAIN_LENGTH) {
            throw new ValidationException('Token header must contain a valid x5c certificate chain.');
        }
    }

    /**
     * Extracts and parses the X.509 certificate chain from the token's x5c header.
     *
     * @param Token $token
     * @return array<OpenSSLCertificate> The parsed certificate chain.
     * @throws ValidationException
     */
    private function extractAndParseCertificateChain(Token $token): array
    {
        $chain = [];
        $x5c = $token->headers()->get('x5c');

        foreach ($x5c as $certData) {
            $parsedCert = $this->base64DerToCert($certData);
            if ($parsedCert === false) {
                throw new ValidationException('Failed to parse a certificate from the x5c header.');
            }
            $chain[] = $parsedCert;
        }

        return $chain;
    }

    /**
     * Verifies the certificate chain against trusted Apple root fingerprints and chain of trust.
     *
     * @param array<OpenSSLCertificate> $chain The certificate chain [leaf, intermediate, root].
     * @throws ValidationException
     */
    private function verifyCertificateChain(array $chain): void
    {
        [$leaf, $intermediate, $root] = $chain;

        $fingerprints = [
            strtolower(openssl_x509_fingerprint($intermediate) ?: ''),
            strtolower(openssl_x509_fingerprint($root) ?: ''),
        ];

        if ($fingerprints !== self::APPLE_CERTIFICATE_FINGERPRINTS) {
            throw new ValidationException('Certificate chain is not rooted to a trusted Apple certificate.');
        }

        if (openssl_x509_verify($leaf, $intermediate) !== 1) {
            throw new ValidationException('Leaf certificate could not be verified with the intermediate certificate.');
        }

        if (openssl_x509_verify($intermediate, $root) !== 1) {
            throw new ValidationException('Intermediate certificate could not be verified with the root certificate.');
        }
    }

    /**
     * Asserts that the token's signature is valid for the given public key.
     *
     * @param Token $token
     * @param OpenSSLCertificate $publicKeyCertificate The certificate containing the public key.
     * @throws ValidationException
     */
    private function assertSignature(Token $token, OpenSSLCertificate $publicKeyCertificate): void
    {
        if (!openssl_x509_export($publicKeyCertificate, $exportedCert)) {
            throw new ValidationException('Failed to export public key from leaf certificate.');
        }

        try {
            $constraint = new SignedWith(new Sha256(), InMemory::plainText($exportedCert));
            $constraint->assert($token);
        } catch (ConstraintViolation $e) {
            throw new ValidationException('JWS signature verification failed.', 0, $e);
        }
    }

    /**
     * Converts a base64-encoded DER certificate string into an OpenSSL certificate resource.
     *
     * @param string $certificate
     * @return OpenSSLCertificate|false
     */
    private function base64DerToCert(string $certificate): OpenSSLCertificate|false
    {
        $pem = "-----BEGIN CERTIFICATE-----\n" .
            chunk_split($certificate, 64) .
            "-----END CERTIFICATE-----\n";

        return openssl_x509_read($pem);
    }
}
