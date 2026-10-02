<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\AppleAppStore\Asn1\AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\Asn1\Phpseclib3AttributeSetDecoder;
use ReceiptValidator\AppleAppStore\Asn1\Phpseclib4AttributeSetDecoder;
use ValueError;

/**
 * Low-level helpers to pull transaction identifiers out of Apple receipts.
 * No signature or PKI validation is performed here.
 *
 * Works with phpseclib 3 or 4; the matching decoder is picked at runtime.
 */
final class ReceiptUtility
{
    /** Receipt attribute: in-app array */
    private const int IN_APP_ARRAY_TYPE = 17;

    /** In-app attribute: transaction identifier */
    private const int TRANSACTION_IDENTIFIER_TYPE = 1703;

    private static ?AttributeSetDecoder $decoder = null;

    private function __construct()
    {
    }

    /**
     * Override the ASN.1 decoder (mainly for tests). Pass null to restore auto-detection.
     */
    public static function setDecoder(?AttributeSetDecoder $decoder): void
    {
        self::$decoder = $decoder;
    }

    /**
     * Extract a transaction ID from a Base64-encoded App Receipt (PKCS#7).
     *
     * @throws ValueError when the receipt fails to decode or isn't a PKCS#7 container.
     */
    public static function extractTransactionIdFromAppReceipt(string $appReceipt): ?string
    {
        $decoded = base64_decode($appReceipt, true);
        if ($decoded === false) {
            throw new ValueError('Failed to Base64-decode the app receipt.');
        }

        foreach (self::decoder()->decodePkcs7Receipt($decoded) as [$type, $value]) {
            if ($type === (string) self::IN_APP_ARRAY_TYPE) {
                return self::findTransactionIdInInAppPurchaseSet($value);
            }
        }

        return null;
    }

    /**
     * Extract a transaction ID from a legacy transactional receipt (Base64).
     * Uses regex on the decoded payload; no validation performed.
     */
    public static function extractTransactionIdFromTransactionReceipt(string $transactionReceipt): ?string
    {
        $decoded = base64_decode($transactionReceipt, true);
        if ($decoded === false) {
            return null;
        }

        if (!preg_match('/"purchase-info"\s*=\s*"([^"]+)";/m', $decoded, $m)) {
            return null;
        }

        $purchaseInfo = base64_decode($m[1], true);
        if ($purchaseInfo === false) {
            return null;
        }

        if (!preg_match('/"transaction-id"\s*=\s*"([^"]+)";/m', $purchaseInfo, $txm)) {
            return null;
        }

        return $txm[1];
    }

    private static function findTransactionIdInInAppPurchaseSet(string $inAppPurchaseData): ?string
    {
        $decoder = self::decoder();

        try {
            $inAppSet = $decoder->decodeAttributeSet($inAppPurchaseData);
        } catch (ValueError) {
            return null;
        }

        foreach ($inAppSet as [$type, $value]) {
            if ($type !== (string) self::TRANSACTION_IDENTIFIER_TYPE) {
                continue;
            }

            try {
                return $decoder->decodeScalar($value);
            } catch (ValueError) {
                return null;
            }
        }

        return null;
    }

    private static function decoder(): AttributeSetDecoder
    {
        return self::$decoder ??= self::detectDecoder();
    }

    private static function detectDecoder(): AttributeSetDecoder
    {
        // Class names are strings so static analysis doesn't require both phpseclib majors to be installed.
        if (class_exists('phpseclib4\File\ASN1')) {
            return new Phpseclib4AttributeSetDecoder();
        }

        if (class_exists('phpseclib3\File\ASN1')) {
            return new Phpseclib3AttributeSetDecoder();
        }

        throw new ValueError('phpseclib/phpseclib ^3.0 or ^4.0 is required to parse app receipts.');
    }
}
