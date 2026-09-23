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
 * certificate, checks that the leaf and intermediate certificates carry Apple's
 * App Store marker extensions and were valid when the token was signed, and
 * finally verifies the token's signature using the public key from the leaf
 * certificate.
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

    /** Marker extension present on Apple's App Store receipt-signing (leaf) certificates. */
    private const string LEAF_MARKER_OID = '1.2.840.113635.100.6.11.1';

    /** Marker extension present on Apple's WWDR intermediate certificates. */
    private const string INTERMEDIATE_MARKER_OID = '1.2.840.113635.100.6.2.1';

    private const int EXPECTED_CHAIN_LENGTH = 3;
    private const string EXPECTED_ALGORITHM = 'ES256';

    /** @var list<string> */
    private array $trustedFingerprints;

    /**
     * @param list<string>|null $trustedFingerprints SHA-1 fingerprints of the [intermediate, root]
     *                                               certificates to trust. Defaults to Apple's.
     *                                               Only override this in tests.
     */
    public function __construct(?array $trustedFingerprints = null)
    {
        $this->trustedFingerprints = array_map(
            strtolower(...),
            $trustedFingerprints ?? self::APPLE_CERTIFICATE_FINGERPRINTS
        );
    }

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
        $this->verifyCertificateChain($chain, $this->effectiveTimestamp($token));

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

        if (!isset($headers['x5c']) || !is_array($headers['x5c']) || count($headers['x5c']) !== self::EXPECTED_CHAIN_LENGTH) {
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
            $parsedCert = is_string($certData) ? $this->base64DerToCert($certData) : false;
            if ($parsedCert === false) {
                throw new ValidationException('Failed to parse a certificate from the x5c header.');
            }
            $chain[] = $parsedCert;
        }

        return $chain;
    }

    /**
     * Returns the time (Unix seconds) at which the certificate chain must be valid.
     *
     * Apple's signed payloads carry a `signedDate` claim (milliseconds). Checking
     * validity at that moment lets older, legitimately signed payloads keep
     * verifying after Apple rotates its signing certificate. Payloads without
     * the claim are checked against the current time.
     */
    private function effectiveTimestamp(Token $token): int
    {
        $signedDate = $token->claims()->get('signedDate');

        if (is_int($signedDate) || (is_string($signedDate) && ctype_digit($signedDate))) {
            return intdiv((int) $signedDate, 1000);
        }

        return time();
    }

    /**
     * Verifies the certificate chain against trusted Apple root fingerprints and chain of trust.
     *
     * @param array<OpenSSLCertificate> $chain The certificate chain [leaf, intermediate, root].
     * @param int $timestamp Unix time at which every certificate must be valid.
     * @throws ValidationException
     */
    private function verifyCertificateChain(array $chain, int $timestamp): void
    {
        [$leaf, $intermediate, $root] = $chain;

        $fingerprints = [
            strtolower(openssl_x509_fingerprint($intermediate) ?: ''),
            strtolower(openssl_x509_fingerprint($root) ?: ''),
        ];

        if ($fingerprints !== $this->trustedFingerprints) {
            throw new ValidationException('Certificate chain is not rooted to a trusted Apple certificate.');
        }

        if (openssl_x509_verify($leaf, $intermediate) !== 1) {
            throw new ValidationException('Leaf certificate could not be verified with the intermediate certificate.');
        }

        if (openssl_x509_verify($intermediate, $root) !== 1) {
            throw new ValidationException('Intermediate certificate could not be verified with the root certificate.');
        }

        $this->assertHasExtension($leaf, self::LEAF_MARKER_OID, 'Leaf certificate is not an App Store signing certificate.');
        $this->assertHasExtension($intermediate, self::INTERMEDIATE_MARKER_OID, 'Intermediate certificate is not an Apple WWDR certificate.');

        foreach ($chain as $certificate) {
            $this->assertValidAt($certificate, $timestamp);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertHasExtension(OpenSSLCertificate $certificate, string $oid, string $message): void
    {
        $parsed = openssl_x509_parse($certificate);

        if (!is_array($parsed) || !isset($parsed['extensions'][$oid])) {
            throw new ValidationException($message);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertValidAt(OpenSSLCertificate $certificate, int $timestamp): void
    {
        $parsed = openssl_x509_parse($certificate);

        $from = is_array($parsed) ? ($parsed['validFrom_time_t'] ?? null) : null;
        $to   = is_array($parsed) ? ($parsed['validTo_time_t'] ?? null) : null;

        if (!is_int($from) || !is_int($to) || $timestamp < $from || $timestamp > $to) {
            throw new ValidationException('Certificate in the x5c chain was not valid at the time the token was signed.');
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
