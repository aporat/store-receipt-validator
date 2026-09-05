<?php

declare(strict_types=1);

namespace ReceiptValidator\AppleAppStore;

use Exception;
use phpseclib4\File\ASN1;
use phpseclib4\File\ASN1\Constructed;
use phpseclib4\File\ASN1\Maps\ContentInfo;
use phpseclib4\File\ASN1\Maps\SignedData;
use Stringable;
use ValueError;

/**
 * Low-level helpers to pull transaction identifiers out of Apple receipts.
 * No signature or PKI validation is performed here.
 */
final class ReceiptUtility
{
    /** PKCS #7: signedData OID (dotted form and phpseclib's symbolic name) */
    private const string PKCS7_OID = '1.2.840.113549.1.7.2';
    private const string PKCS7_OID_NAME = 'id-signedData';

    /** Receipt attribute: in-app array */
    private const int IN_APP_ARRAY_TYPE = 17;

    /** In-app attribute: transaction identifier */
    private const int TRANSACTION_IDENTIFIER_TYPE = 1703;

    /**
     * ASN.1 schema shared by the receipt attribute set and the in-app purchase set:
     * SET OF SEQUENCE { type INTEGER, version INTEGER, value OCTET STRING }.
     *
     * @var array<string, mixed>
     */
    private const array ATTRIBUTE_SET_MAP = [
        'type' => ASN1::TYPE_SET,
        'min' => 0,
        'max' => -1,
        'children' => [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'type'    => ['type' => ASN1::TYPE_INTEGER],
                'version' => ['type' => ASN1::TYPE_INTEGER],
                'value'   => ['type' => ASN1::TYPE_OCTET_STRING],
            ],
        ],
    ];

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
     * @return list<array{string, string}>
     * @throws ValueError
     */
    private static function getReceiptAttributeSet(string $der): array
    {
        try {
            $contentInfo = self::map($der, ContentInfo::MAP);
            $oid = self::toString($contentInfo['contentType']);
        } catch (Exception $e) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.', 0, $e);
        }

        if ($oid !== self::PKCS7_OID && $oid !== self::PKCS7_OID_NAME) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.');
        }

        try {
            // ContentInfo.content is an EXPLICIT [0] wrapper around SignedData; re-decode its raw bytes.
            $content = $contentInfo['content'];
            if (!$content instanceof Stringable) {
                throw new ValueError('Could not find the receipt attribute set in the payload.');
            }

            $signedData = self::map((string) $content, SignedData::MAP);
            $encap = $signedData['encapContentInfo'];
            $eContent = $encap instanceof Constructed ? $encap['eContent'] : null;

            if (!$eContent instanceof Stringable) {
                throw new ValueError('Could not find the receipt attribute set in the payload.');
            }

            return self::decodeAttributeSet((string) $eContent);
        } catch (Exception $e) {
            throw new ValueError('Could not find the receipt attribute set in the payload.', 0, $e);
        }
    }

    private static function findTransactionIdInInAppPurchaseSet(string $inAppPurchaseData): ?string
    {
        try {
            $inAppSet = self::decodeAttributeSet($inAppPurchaseData);
        } catch (Exception) {
            return null;
        }

        foreach ($inAppSet as [$type, $value]) {
            if ($type === (string) self::TRANSACTION_IDENTIFIER_TYPE) {
                try {
                    $content = ASN1::decodeBER($value)['content'] ?? null;
                } catch (Exception) {
                    return null;
                }

                return $content instanceof Constructed ? null : self::toStringOrNull($content);
            }
        }

        return null;
    }

    /**
     * Decode a BER-encoded attribute set into [type, value] pairs.
     *
     * @return list<array{string, string}>
     * @throws Exception on malformed input (phpseclib exceptions)
     */
    private static function decodeAttributeSet(string $ber): array
    {
        $pairs = [];

        foreach (self::map($ber, self::ATTRIBUTE_SET_MAP) as $attribute) {
            if (!$attribute instanceof Constructed) {
                continue;
            }

            $pairs[] = [self::toString($attribute['type']), self::toString($attribute['value'])];
        }

        return $pairs;
    }

    /**
     * Decode BER and bind it to an ASN.1 map, requiring a constructed (SEQUENCE/SET) result.
     *
     * @param array<string, mixed> $mapping
     * @throws Exception on malformed input (phpseclib exceptions)
     */
    private static function map(string $ber, array $mapping): Constructed
    {
        $mapped = ASN1::map(ASN1::decodeBER($ber), $mapping);

        if (!$mapped instanceof Constructed) {
            throw new ValueError('Unexpected ASN.1 structure: expected a constructed type.');
        }

        return $mapped;
    }

    private static function toString(mixed $value): string
    {
        $string = self::toStringOrNull($value);

        if ($string === null) {
            throw new ValueError('Unexpected ASN.1 structure: expected a scalar value.');
        }

        return $string;
    }

    private static function toStringOrNull(mixed $value): ?string
    {
        if ($value instanceof Stringable || is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }
}
