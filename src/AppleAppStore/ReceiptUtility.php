<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use ReceiptValidator\Support\Asn1Node;
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

        foreach (self::getReceiptAttributeSet($decoded) as [$type, $value]) {
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

    /**
     * Decode outer PKCS#7 and return the set of receipt attributes as [type, value] pairs.
     *
     * ContentInfo ::= SEQUENCE { contentType OID, content [0] EXPLICIT SignedData }
     * SignedData  ::= SEQUENCE { version, digestAlgorithms, encapContentInfo, ... }
     * EncapsulatedContentInfo ::= SEQUENCE { eContentType OID, eContent [0] EXPLICIT OCTET STRING }
     *
     * @return list<array{string, string}>
     * @throws ValueError
     */
    private static function getReceiptAttributeSet(string $der): array
    {
        try {
            $contentInfo = Asn1Node::decode($der);
            $contentType = $contentInfo->child(0);

            $isPkcs7 = $contentInfo->is(Asn1Node::TAG_SEQUENCE)
                && $contentType !== null
                && $contentType->is(Asn1Node::TAG_OBJECT_IDENTIFIER)
                && $contentType->oid() === self::PKCS7_OID;
        } catch (ValueError $e) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.', 0, $e);
        }

        if (!$isPkcs7) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.');
        }

        $eContent = $contentInfo->child(1)?->child(0)?->child(2)?->child(1)?->child(0);

        if ($eContent === null || !$eContent->is(Asn1Node::TAG_OCTET_STRING)) {
            throw new ValueError('Could not find the receipt attribute set in the payload.');
        }

        try {
            return self::decodeAttributeSet($eContent->octets());
        } catch (ValueError $e) {
            throw new ValueError('Could not find the receipt attribute set in the payload.', 0, $e);
        }
    }

    private static function findTransactionIdInInAppPurchaseSet(string $inAppPurchaseData): ?string
    {
        try {
            $inAppSet = self::decodeAttributeSet($inAppPurchaseData);
        } catch (ValueError) {
            return null;
        }

        foreach ($inAppSet as [$type, $value]) {
            if ($type !== (string) self::TRANSACTION_IDENTIFIER_TYPE) {
                continue;
            }

            try {
                $node = Asn1Node::decode($value);
            } catch (ValueError) {
                return null;
            }

            return $node->constructed ? null : $node->content;
        }

        return null;
    }

    /**
     * Decode a BER-encoded attribute set into [type, value] pairs:
     * SET OF SEQUENCE { type INTEGER, version INTEGER, value OCTET STRING }.
     *
     * @return list<array{string, string}>
     * @throws ValueError on malformed input
     */
    private static function decodeAttributeSet(string $ber): array
    {
        $set = Asn1Node::decode($ber);

        if (!$set->is(Asn1Node::TAG_SET)) {
            throw new ValueError('Unexpected ASN.1 structure: expected a SET of attributes.');
        }

        $pairs = [];

        foreach ($set->children as $attribute) {
            $type = $attribute->child(0);
            $value = $attribute->child(2);

            if (
                !$attribute->is(Asn1Node::TAG_SEQUENCE)
                || $type === null
                || !$type->is(Asn1Node::TAG_INTEGER)
                || $value === null
                || !$value->is(Asn1Node::TAG_OCTET_STRING)
            ) {
                continue;
            }

            $pairs[] = [$type->integer(), $value->octets()];
        }

        return $pairs;
    }
}
