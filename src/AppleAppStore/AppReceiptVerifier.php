<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use OpenSSLCertificate;
use ReceiptValidator\AppleAppStore\Asn1\AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\Asn1\Phpseclib3AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\Asn1\Phpseclib4AttributeSetDecoder;
use ReceiptValidator\Exceptions\ValidationException;
use ValueError;

/**
 * Verifies a StoreKit 1 app receipt offline and reads it only from the bytes
 * Apple's signature covers.
 *
 * An app receipt is the only proof of purchase a StoreKit 1 app can send, and
 * {@see ReceiptUtility} reads it without checking anything. On its own that
 * lets any client wrap a transaction id it happens to know in a receipt-shaped
 * blob and have that purchase looked up as its own. Verify first, then read.
 *
 * What is checked, with no network call:
 *
 * - the PKCS #7 signature over the receipt contents, by a single signer;
 * - the signer is an App Store receipt signing certificate
 *   (OID 1.2.840.113635.100.6.11.1), issued by an Apple Worldwide Developer
 *   Relations intermediate (OID 1.2.840.113635.100.6.2.1, a CA) that the
 *   receipt embeds, issued in turn by the Apple Root CA shipped with this
 *   package (or the root you pass in);
 * - the receipt's bundle id is present, in the signed contents.
 *
 * Certificate validity dates are deliberately not checked. Apple's receipt
 * signing certificates are short-lived and a receipt signed by one that has
 * since expired stays valid; Apple's guidance is to verify the chain, not the
 * dates. A time-checked chain would reject every older receipt on a device
 * that has not refreshed it. The signature and the pinned root are what prove
 * the receipt came from Apple.
 *
 * Works with phpseclib 3 or 4; the matching decoder is picked at runtime.
 */
final class AppReceiptVerifier
{
    /** SHA-256 fingerprint of the Apple Root CA (valid until 2035-02-09), lower-case hex. */
    public const string APPLE_ROOT_CA_FINGERPRINT = 'b0b1730ecbc7ff4505142c49f1295e6eda6bcaed7e2c68c5be91b5a11001f024';

    /** Apple's receipts are a few KB; this only bounds the work an abusive input can cause. */
    public const int MAX_BYTES = 2 * 1024 * 1024;

    private const string APPLE_ROOT_CA_PATH = __DIR__ . '/../../resources/AppleIncRootCertificate.pem';

    private const string OID_RECEIPT_SIGNING = '1.2.840.113635.100.6.11.1';

    private const string OID_WWDR_INTERMEDIATE = '1.2.840.113635.100.6.2.1';

    private const int ATTRIBUTE_BUNDLE_ID = 2;

    private const int ATTRIBUTE_IN_APP = 17;

    private const int IN_APP_TRANSACTION_ID = 1703;

    private readonly string $trustedRootPem;

    private ?AttributeSetDecoder $decoder;

    /**
     * @param string|null $trustedRootPem A PEM root to trust instead of the Apple Root CA shipped with the
     *                                    package. Only tests that sign their own receipts should need it.
     * @param AttributeSetDecoder|null $decoder The ASN.1 decoder; auto-detected from the installed phpseclib.
     * @throws ValidationException When the shipped root certificate cannot be read.
     */
    public function __construct(?string $trustedRootPem = null, ?AttributeSetDecoder $decoder = null)
    {
        if ($trustedRootPem === null) {
            $pem = @file_get_contents(self::APPLE_ROOT_CA_PATH);
            if ($pem === false || $pem === '') {
                throw new ValidationException('The Apple Root CA certificate shipped with this package is missing.');
            }
            $trustedRootPem = $pem;
        }

        $this->trustedRootPem = $trustedRootPem;
        $this->decoder = $decoder;
    }

    /**
     * Verify a Base64 app receipt as the app posts it.
     *
     * @throws ValidationException When the receipt is malformed, not signed by Apple, or unreadable.
     */
    public function verify(string $appReceipt): AppReceipt
    {
        if (strlen($appReceipt) > self::MAX_BYTES * 4 / 3 + 4) {
            throw new ValidationException('App receipt is too large.');
        }

        $der = base64_decode(preg_replace('/\s+/', '', $appReceipt) ?? '', true);
        if ($der === false || $der === '') {
            throw new ValidationException('App receipt is not Base64.');
        }

        return $this->read($this->verifiedContent($der));
    }

    /**
     * The receipt contents, once the signature over them and the signer's chain
     * up to the trusted root have both checked out.
     *
     * @throws ValidationException
     */
    private function verifiedContent(string $der): string
    {
        $in = $this->tempFile($der);
        $signer = $this->tempFile('');
        $content = $this->tempFile('');

        try {
            // NOVERIFY skips OpenSSL's own chain building, which checks validity
            // dates (see the class doc); the chain is checked below against the
            // trusted root instead. The signature itself is still checked.
            $verified = openssl_cms_verify(
                $in,
                OPENSSL_CMS_NOVERIFY | OPENSSL_CMS_BINARY,
                $signer,
                [],
                null,
                $content,
                null,
                null,
                OPENSSL_ENCODING_DER,
            );

            if ($verified !== true) {
                throw new ValidationException('App receipt signature does not verify.');
            }

            $signerPem = (string) file_get_contents($signer);
            if (preg_match_all('/-----BEGIN CERTIFICATE-----/', $signerPem) !== 1) {
                throw new ValidationException('App receipt must have exactly one signer.');
            }

            $this->assertAppleChain($signerPem, $this->embeddedCertificates($der));

            return (string) file_get_contents($content);
        } finally {
            while (openssl_error_string() !== false) {
                // Drain OpenSSL's error queue so it does not leak into later calls.
            }

            @unlink($in);
            @unlink($signer);
            @unlink($content);
        }
    }

    /**
     * @param array<int, string> $embedded Every certificate the receipt carries, PEM.
     * @throws ValidationException
     */
    private function assertAppleChain(string $signerPem, array $embedded): void
    {
        $leaf = openssl_x509_read($signerPem);
        $root = openssl_x509_read($this->trustedRootPem);

        if ($leaf === false || $root === false) {
            throw new ValidationException('App receipt certificates are unreadable.');
        }

        if (!$this->hasExtension($leaf, self::OID_RECEIPT_SIGNING)) {
            throw new ValidationException(
                'App receipt is not signed by an App Store receipt signing certificate.'
            );
        }

        foreach ($embedded as $pem) {
            $intermediate = openssl_x509_read($pem);
            if ($intermediate === false) {
                continue;
            }

            if (openssl_x509_verify($leaf, $intermediate) !== 1) {
                continue;
            }

            $parsed = openssl_x509_parse($intermediate);
            $isCa = is_array($parsed)
                && str_contains((string) ($parsed['extensions']['basicConstraints'] ?? ''), 'CA:TRUE');

            if (
                $isCa
                && $this->hasExtension($intermediate, self::OID_WWDR_INTERMEDIATE)
                && openssl_x509_verify($intermediate, $root) === 1
            ) {
                return;
            }
        }

        throw new ValidationException(
            'App receipt is not signed by a certificate chaining to the Apple Root CA.'
        );
    }

    /**
     * Every certificate the receipt carries. openssl_cms_verify cannot hand them
     * over without building the chain itself, so they are read from the same
     * bytes as PKCS #7.
     *
     * @return array<int, string>
     * @throws ValidationException
     */
    private function embeddedCertificates(string $der): array
    {
        $pem = "-----BEGIN PKCS7-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PKCS7-----\n";

        $certificates = [];
        if (!openssl_pkcs7_read($pem, $certificates)) {
            throw new ValidationException('App receipt carries no certificates.');
        }

        return $certificates;
    }

    private function hasExtension(OpenSSLCertificate $certificate, string $oid): bool
    {
        $parsed = openssl_x509_parse($certificate);

        return is_array($parsed)
            && is_array($parsed['extensions'] ?? null)
            && array_key_exists($oid, $parsed['extensions']);
    }

    /**
     * @throws ValidationException
     */
    private function read(string $content): AppReceipt
    {
        $decoder = $this->decoder();

        try {
            $attributes = $decoder->decodeAttributeSet($content);
        } catch (ValueError $e) {
            throw new ValidationException('App receipt contents are unreadable.', 0, $e);
        }

        $bundleId = null;
        $transactionIds = [];

        foreach ($attributes as [$type, $value]) {
            if ($type === (string) self::ATTRIBUTE_BUNDLE_ID && $bundleId === null) {
                $bundleId = $this->scalar($decoder, $value);
            }

            if ($type === (string) self::ATTRIBUTE_IN_APP) {
                $transactionId = $this->inAppTransactionId($decoder, $value);
                if ($transactionId !== null) {
                    $transactionIds[] = $transactionId;
                }
            }
        }

        if ($bundleId === null || $bundleId === '') {
            throw new ValidationException('App receipt has no bundle id.');
        }

        return new AppReceipt($bundleId, $transactionIds);
    }

    private function inAppTransactionId(AttributeSetDecoder $decoder, string $inApp): ?string
    {
        try {
            foreach ($decoder->decodeAttributeSet($inApp) as [$type, $value]) {
                if ($type === (string) self::IN_APP_TRANSACTION_ID) {
                    return $this->scalar($decoder, $value);
                }
            }
        } catch (ValueError) {
            return null;
        }

        return null;
    }

    private function scalar(AttributeSetDecoder $decoder, string $value): ?string
    {
        try {
            return $decoder->decodeScalar($value);
        } catch (ValueError) {
            return null;
        }
    }

    /**
     * @throws ValidationException
     */
    private function decoder(): AttributeSetDecoder
    {
        if ($this->decoder !== null) {
            return $this->decoder;
        }

        // Class names are strings so static analysis does not require both phpseclib majors.
        if (class_exists('phpseclib4\File\ASN1')) {
            return $this->decoder = new Phpseclib4AttributeSetDecoder();
        }

        if (class_exists('phpseclib3\File\ASN1')) {
            return $this->decoder = new Phpseclib3AttributeSetDecoder();
        }

        throw new ValidationException('phpseclib/phpseclib ^3.0 or ^4.0 is required to read app receipts.');
    }

    /**
     * @throws ValidationException
     */
    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'receipt');
        if ($path === false || file_put_contents($path, $contents) === false) {
            throw new ValidationException('Could not stage the app receipt for verification.');
        }

        return $path;
    }
}
