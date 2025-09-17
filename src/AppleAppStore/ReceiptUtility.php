<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use phpseclib3\File\ASN1;
use ValueError;

/**
 * Low-level helpers to pull transaction identifiers out of Apple receipts.
 * No signature or PKI validation is performed here.
 */
final class ReceiptUtility
{
    /** PKCS #7: signedData OID */
    private const string PKCS7_OID = '1.2.840.113549.1.7.2';

    /** Receipt attribute: in-app array */
    private const int IN_APP_ARRAY_TYPE = 17;

    /** In-app attribute: transaction identifier */
    private const int TRANSACTION_IDENTIFIER_TYPE = 1703;

    private function __construct()
    {
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

        $attributes = self::getReceiptAttributeSet($decoded);

        foreach ($attributes as $attr) {
            $type  = $attr['content'][0]['content'] ?? null;
            $value = $attr['content'][2]['content'] ?? null;

            if ((string) $type === (string) self::IN_APP_ARRAY_TYPE && is_string($value)) {
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

    /**
     * Decode outer PKCS#7 and return the set of receipt attributes.
     *
     * @return array<int, mixed>
     * @throws ValueError
     */
    private static function getReceiptAttributeSet(string $der): array
    {
        $root = ASN1::decodeBER($der);
        $sequence = $root[0]['content'] ?? null;

        // Guard the OID node
        $oid = $sequence[0]['content'] ?? null;
        if (!is_string($oid) || $oid !== self::PKCS7_OID) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.');
        }

        // Walk down to the encapsulated receipt (as BER) and decode it
        $data = $sequence[1]['content'][0]['content'][2]['content'][1]['content'][0]['content'] ?? null;
        if (!is_string($data)) {
            throw new ValueError('Could not find the receipt attribute set in the payload.');
        }

        $decodedSet = ASN1::decodeBER($data);
        $attrs = $decodedSet[0]['content'] ?? null;

        return is_array($attrs) ? $attrs : [];
    }

    private static function findTransactionIdInInAppPurchaseSet(string $inAppPurchaseData): ?string
    {
        $inAppDecoded = ASN1::decodeBER($inAppPurchaseData);
        $inAppSet = $inAppDecoded[0]['content'] ?? [];

        foreach ($inAppSet as $item) {
            $type  = $item['content'][0]['content'] ?? null;
            $value = $item['content'][2]['content'] ?? null;

            if ((string) $type === (string) self::TRANSACTION_IDENTIFIER_TYPE && is_string($value)) {
                $final = ASN1::decodeBER($value);
                $content = $final[0]['content'] ?? null;
                return is_scalar($content) ? (string) $content : null;
            }
        }

        return null;
    }
}
