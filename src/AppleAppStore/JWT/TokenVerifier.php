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
 * Follows the chain rules of Apple's own App Store Server Library
 * (`ChainVerifier` / `SignedDataVerifier` in apple/app-store-server-library-java,
 * -python and -node), without the optional online OCSP checks:
 *
 * - the `alg` header is ES256;
 * - the `x5c` header holds exactly three certificates, leaf → intermediate → root;
 * - the root is a trusted root certificate: its fingerprint is pinned (Apple Root CA - G3
 *   by default) and it is self-signed;
 * - the intermediate is issued by that root (issuer name and signature), is a CA
 *   (basicConstraints CA:TRUE) and carries Apple's WWDR marker extension
 *   1.2.840.113635.100.6.2.1;
 * - the leaf is issued by the intermediate (issuer name and signature) and carries the
 *   App Store signing marker extension 1.2.840.113635.100.6.11.1;
 * - all three certificates are valid at the payload's `signedDate` (falling back to
 *   `receiptCreationDate`, then to the current time), which is what Apple's libraries
 *   do with online checks off, so older payloads keep verifying after Apple rotates
 *   its signing certificates;
 * - the JWS signature verifies with the leaf's public key.
 *
 * The WWDR intermediate is not pinned: Apple rotates intermediates (G6 today), and any
 * intermediate meeting the rules above under a trusted root is accepted, as in Apple's
 * libraries.
 */
final class TokenVerifier
{
    /**
     * SHA-1 fingerprint of Apple Root CA - G3, the root of every App Store signing chain.
     * https://www.apple.com/certificateauthority/AppleRootCA-G3.cer (SHA-256
     * 63343abfb89a6a03ebb57e9b3f5fa7be7c4f5c756f3017b3a8c488c3653e9179), valid until 2039-04-30.
     */
    public const string APPLE_ROOT_CA_G3_FINGERPRINT = 'b52cb02fd567e0359fe8fa4d4c41037970fe01b0';

    /** Marker extension present on Apple's App Store receipt-signing (leaf) certificates. */
    private const string LEAF_MARKER_OID = '1.2.840.113635.100.6.11.1';

    /** Marker extension present on Apple's WWDR intermediate certificates. */
    private const string INTERMEDIATE_MARKER_OID = '1.2.840.113635.100.6.2.1';

    private const int EXPECTED_CHAIN_LENGTH = 3;
    private const string EXPECTED_ALGORITHM = 'ES256';

    /** @var list<string> Lower-case hex SHA-1 or SHA-256 fingerprints of trusted root certificates. */
    private array $trustedRootFingerprints;

    /**
     * @param list<string>|null $trustedFingerprints Fingerprints (SHA-1 or SHA-256, hex, colons
     *                                               optional) of the root certificates to trust.
     *                                               Defaults to Apple Root CA - G3. Override it
     *                                               only for tests or a private test chain.
     *                                               Lists written for earlier versions, which
     *                                               named [intermediate, root], keep working:
     *                                               the root's entry is what is matched, and a
     *                                               non-self-signed certificate is never taken
     *                                               as a root.
     */
    public function __construct(?array $trustedFingerprints = null)
    {
        $this->setTrustedRootFingerprints($trustedFingerprints ?? [self::APPLE_ROOT_CA_G3_FINGERPRINT]);
    }

    /**
     * Replace the trusted root certificates.
     *
     * @param array<mixed> $fingerprints Fingerprint strings (SHA-1 or SHA-256, hex, colons optional).
     * @throws ValidationException When the list is empty or holds something that isn't a fingerprint.
     */
    public function setTrustedRootFingerprints(array $fingerprints): static
    {
        $normalized = [];

        foreach ($fingerprints as $fingerprint) {
            $hex = is_string($fingerprint) ? strtolower(str_replace(':', '', trim($fingerprint))) : '';

            if (preg_match('/\A(?:[0-9a-f]{40}|[0-9a-f]{64})\z/', $hex) !== 1) {
                throw new ValidationException('Trusted root fingerprints must be SHA-1 or SHA-256 hex digests.');
            }

            $normalized[] = $hex;
        }

        if ($normalized === []) {
            throw new ValidationException('At least one trusted root fingerprint is required.');
        }

        $this->trustedRootFingerprints = array_values(array_unique($normalized));

        return $this;
    }

    /**
     * @return list<string> The trusted root fingerprints, lower-case hex.
     */
    public function getTrustedRootFingerprints(): array
    {
        return $this->trustedRootFingerprints;
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
     * @return array{OpenSSLCertificate, OpenSSLCertificate, OpenSSLCertificate} [leaf, intermediate, root]
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

        return [$chain[0], $chain[1], $chain[2]];
    }

    /**
     * Returns the time (Unix seconds) at which the certificate chain must be valid.
     *
     * Apple's signed payloads carry a `signedDate` claim (milliseconds); app transactions
     * also carry `receiptCreationDate`. As in Apple's libraries with online checks off, the
     * chain is checked at that moment, so older, legitimately signed payloads keep
     * verifying after Apple rotates its signing certificate. A payload with neither claim
     * is checked against the current time.
     */
    private function effectiveTimestamp(Token $token): int
    {
        foreach (['signedDate', 'receiptCreationDate'] as $claim) {
            $value = $token->claims()->get($claim);

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                return intdiv((int) $value, 1000);
            }
        }

        return time();
    }

    /**
     * Verifies the certificate chain against the trusted roots.
     *
     * @param array{OpenSSLCertificate, OpenSSLCertificate, OpenSSLCertificate} $chain [leaf, intermediate, root]
     * @param int $timestamp Unix time at which every certificate must be valid.
     * @throws ValidationException
     */
    private function verifyCertificateChain(array $chain, int $timestamp): void
    {
        [$leaf, $intermediate, $root] = $chain;
        [$leafInfo, $intermediateInfo, $rootInfo] = array_map($this->parse(...), $chain);

        if (!$this->isTrustedRoot($root, $rootInfo)) {
            throw new ValidationException('Certificate chain is not rooted to a trusted Apple certificate.');
        }

        if ($intermediateInfo['issuer'] !== $rootInfo['subject'] || openssl_x509_verify($intermediate, $root) !== 1) {
            throw new ValidationException('Intermediate certificate could not be verified with the root certificate.');
        }

        if ($leafInfo['issuer'] !== $intermediateInfo['subject'] || openssl_x509_verify($leaf, $intermediate) !== 1) {
            throw new ValidationException('Leaf certificate could not be verified with the intermediate certificate.');
        }

        if (!isset($intermediateInfo['extensions'][self::INTERMEDIATE_MARKER_OID])) {
            throw new ValidationException('Intermediate certificate is not an Apple WWDR certificate.');
        }

        if (!$this->isCertificateAuthority($intermediateInfo)) {
            throw new ValidationException('Intermediate certificate is not a certificate authority.');
        }

        if (!isset($leafInfo['extensions'][self::LEAF_MARKER_OID])) {
            throw new ValidationException('Leaf certificate is not an App Store signing certificate.');
        }

        foreach ([$leafInfo, $intermediateInfo, $rootInfo] as $info) {
            $this->assertValidAt($info, $timestamp);
        }
    }

    /**
     * A root is trusted when its fingerprint is pinned and it is self-signed. The
     * self-signature check keeps a pinned certificate that isn't a root (such as the
     * intermediate in a list written for earlier versions) from anchoring a chain.
     *
     * @param array<string, mixed> $info
     */
    private function isTrustedRoot(OpenSSLCertificate $root, array $info): bool
    {
        $fingerprints = [
            strtolower((string) openssl_x509_fingerprint($root, 'sha1')),
            strtolower((string) openssl_x509_fingerprint($root, 'sha256')),
        ];

        if (array_intersect($fingerprints, $this->trustedRootFingerprints) === []) {
            return false;
        }

        return $info['issuer'] === $info['subject'] && openssl_x509_verify($root, $root) === 1;
    }

    /**
     * @param array<string, mixed> $info
     */
    private function isCertificateAuthority(array $info): bool
    {
        $basicConstraints = $info['extensions']['basicConstraints'] ?? null;

        return is_string($basicConstraints)
            && preg_match('/(?:^|,)\s*CA:TRUE\s*(?:,|$)/i', $basicConstraints) === 1;
    }

    /**
     * @return array<string, mixed>
     * @throws ValidationException
     */
    private function parse(OpenSSLCertificate $certificate): array
    {
        $parsed = openssl_x509_parse($certificate);

        if (!is_array($parsed)) {
            throw new ValidationException('Failed to parse a certificate from the x5c header.');
        }

        return $parsed + ['issuer' => null, 'subject' => null, 'extensions' => []];
    }

    /**
     * @param array<string, mixed> $info
     * @throws ValidationException
     */
    private function assertValidAt(array $info, int $timestamp): void
    {
        $from = $info['validFrom_time_t'] ?? null;
        $to   = $info['validTo_time_t'] ?? null;

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
            throw new ValidationException('Failed to export public key from leaf certificate.'); // @codeCoverageIgnore
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

        // Silenced: a malformed certificate is reported as a ValidationException by the
        // caller, not as a PHP warning (which frameworks turn into an ErrorException).
        $certificate = @openssl_x509_read($pem);

        while (openssl_error_string() !== false) {
            // Drain OpenSSL's error queue so it doesn't leak into later calls.
        }

        return $certificate;
    }
}
