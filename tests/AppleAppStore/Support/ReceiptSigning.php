<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore\Support;

use RuntimeException;

/**
 * Builds StoreKit 1 app receipts: the receipt attribute set, wrapped in PKCS #7
 * SignedData the way Apple ships it.
 *
 * signedReceipt() signs with the test chain in tests/AppleAppStore/certs/storekit,
 * whose leaf and intermediate carry Apple's receipt-signing and WWDR OIDs; a
 * verifier built with testRootPem() trusts that chain's root in place of
 * Apple's. unsignedReceipt() is the forgery an unverified read accepts: the
 * same structure with no signature at all.
 */
trait ReceiptSigning
{
    private static string $receiptCerts = __DIR__ . '/../certs/storekit';

    protected static function testRootPem(): string
    {
        return (string) file_get_contents(self::$receiptCerts . '/root.pem');
    }

    /**
     * A Base64 receipt for $bundleId holding one in-app purchase per transaction
     * id, signed by the test chain, or by $signer: [cert PEM, key PEM, optional
     * extra certificates PEM] to sign with something else.
     *
     * @param list<string> $transactionIds
     * @param array{0: string, 1: string, 2?: string}|null $signer
     */
    protected static function signedReceipt(string $bundleId, array $transactionIds, ?array $signer = null): string
    {
        $in = (string) tempnam(sys_get_temp_dir(), 'rcpt');
        $out = (string) tempnam(sys_get_temp_dir(), 'rcpt');
        $extra = (string) tempnam(sys_get_temp_dir(), 'rcpt');

        try {
            file_put_contents($in, self::receiptPayload($bundleId, $transactionIds));

            // Extra certificates to embed: the test intermediate by default.
            $extraCertificates = $signer === null
                ? (string) file_get_contents(self::$receiptCerts . '/intermediate.pem')
                : ($signer[2] ?? null);
            if ($extraCertificates !== null) {
                file_put_contents($extra, $extraCertificates);
            }

            $signed = openssl_cms_sign(
                $in,
                $out,
                $signer[0] ?? (string) file_get_contents(self::$receiptCerts . '/leaf.pem'),
                $signer[1] ?? (string) file_get_contents(self::$receiptCerts . '/leaf.p8'),
                [],
                OPENSSL_CMS_BINARY,
                OPENSSL_ENCODING_DER,
                $extraCertificates === null ? null : $extra,
            );

            if (!$signed) {
                throw new RuntimeException('Could not sign the test receipt: ' . openssl_error_string());
            }

            return base64_encode((string) file_get_contents($out));
        } finally {
            @unlink($in);
            @unlink($out);
            @unlink($extra);
        }
    }

    /**
     * A signer from the chain-variants fixtures: [cert, key, embedded intermediate].
     *
     * @return array{0: string, 1: string, 2: string}
     */
    protected static function variantSigner(string $leaf, string $intermediate): array
    {
        $dir = __DIR__ . '/../certs/chain-variants';

        return [
            (string) file_get_contents("{$dir}/{$leaf}.pem"),
            (string) file_get_contents("{$dir}/leaf.p8"),
            (string) file_get_contents("{$dir}/{$intermediate}.pem"),
        ];
    }

    /**
     * The same receipt with no signer and no certificates: PKCS #7 SignedData
     * around the attribute set and nothing else.
     *
     * @param list<string> $transactionIds
     */
    protected static function unsignedReceipt(string $bundleId, array $transactionIds): string
    {
        $signedData = self::asn1(
            0x30,
            self::asn1(0x02, "\x01")                                      // version
            . self::asn1(0x31, '')                                        // digestAlgorithms
            . self::asn1(                                                 // encapContentInfo
                0x30,
                self::asn1(0x06, "\x2A\x86\x48\x86\xF7\x0D\x01\x07\x01")  // id-data
                . self::asn1(0xA0, self::asn1(0x04, self::receiptPayload($bundleId, $transactionIds)))
            )
            . self::asn1(0x31, '')                                        // signerInfos
        );

        return base64_encode(self::asn1(
            0x30,
            self::asn1(0x06, "\x2A\x86\x48\x86\xF7\x0D\x01\x07\x02")      // id-signedData
            . self::asn1(0xA0, $signedData)
        ));
    }

    /**
     * A self-signed certificate (certs/storekit/forged-signer.*) carrying Apple's
     * receipt-signing and WWDR OIDs, for receipts that look like Apple's but
     * are not. Signs alone: OpenSSL before 3.2 refuses to embed the signer
     * certificate a second time as an extra certificate.
     *
     * @return array{0: string, 1: string}
     */
    protected static function selfSignedReceiptSigner(): array
    {
        return [
            (string) file_get_contents(self::$receiptCerts . '/forged-signer.pem'),
            (string) file_get_contents(self::$receiptCerts . '/forged-signer.p8'),
        ];
    }

    /**
     * The receipt attribute set: bundle id (2) and an in-app set (17) per
     * purchase, each with product id (1702), transaction id (1703) and original
     * transaction id (1705).
     *
     * @param list<string> $transactionIds
     */
    private static function receiptPayload(string $bundleId, array $transactionIds): string
    {
        $attributes = [self::receiptAttribute(2, self::asn1(0x0C, $bundleId))];

        foreach ($transactionIds as $transactionId) {
            $attributes[] = self::receiptAttribute(17, self::asn1(
                0x31,
                self::receiptAttribute(1702, self::asn1(0x0C, 'com.example.plan.monthly'))
                . self::receiptAttribute(1703, self::asn1(0x0C, $transactionId))
                . self::receiptAttribute(1705, self::asn1(0x0C, $transactionId))
            ));
        }

        return self::asn1(0x31, implode('', $attributes));
    }

    /** ReceiptAttribute ::= SEQUENCE { type INTEGER, version INTEGER, value OCTET STRING } */
    private static function receiptAttribute(int $type, string $value): string
    {
        return self::asn1(0x30, self::derInteger($type) . self::derInteger(1) . self::asn1(0x04, $value));
    }

    private static function derInteger(int $value): string
    {
        $bytes = ltrim(pack('J', $value), "\x00");
        if ($bytes === '' || ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }

        return self::asn1(0x02, $bytes);
    }

    private static function asn1(int $tag, string $content): string
    {
        $length = strlen($content);
        if ($length < 0x80) {
            return chr($tag) . chr($length) . $content;
        }

        $lengthBytes = ltrim(pack('N', $length), "\x00");

        return chr($tag) . chr(0x80 | strlen($lengthBytes)) . $lengthBytes . $content;
    }
}
