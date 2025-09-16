<?php

namespace ReceiptValidator\AppleAppStore;

use phpseclib3\File\ASN1;
use ValueError;

/**
 * A utility class for performing low-level parsing of Apple's receipt formats.
 *
 * This class provides methods to extract transaction identifiers directly from
 * encoded receipt data. It performs no cryptographic validation and should only
 * be used to retrieve a transaction ID for use with the App Store Server API.
 */
final class ReceiptUtility
{
    /**
     * The ASN.1 Object Identifier for a PKCS #7 signed data structure.
     */
    private const string PKCS7_OID = '1.2.840.113549.1.7.2';

    /**
     * The ASN.1 type for the attribute containing the in-app purchase receipts array.
     */
    private const int IN_APP_ARRAY_TYPE = 17;

    /**
     * The ASN.1 type for the attribute containing a transaction identifier.
     */
    private const int TRANSACTION_IDENTIFIER_TYPE = 1703;

    /**
     * This is a static utility class and should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Extracts a transaction ID from a Base64-encoded App Receipt.
     *
     * This method navigates the ASN.1 structure of an app receipt to find the
     * first available transaction identifier within the in-app purchase array.
     * It performs no validation.
     *
     * @param string $appReceipt The raw, Base64-encoded app receipt string.
     * @return string|null A transaction ID, or null if no in-app purchases are found.
     * @throws ValueError If the receipt is malformed or not in the expected PKCS #7 format.
     */
    public static function extractTransactionIdFromAppReceipt(string $appReceipt): ?string
    {
        $decodedReceipt = base64_decode($appReceipt);
        if ($decodedReceipt === false) {
            throw new ValueError('Failed to Base64-decode the app receipt.');
        }

        $receiptSet = self::getReceiptAttributeSet($decodedReceipt);

        foreach ($receiptSet as $attribute) {
            $type = $attribute['content'][0]['content'] ?? null;
            $value = $attribute['content'][2]['content'] ?? null;

            if ((string)$type === (string)self::IN_APP_ARRAY_TYPE && is_string($value)) {
                return self::findTransactionIdInInAppPurchaseSet($value);
            }
        }

        return null;
    }

    /**
     * Extracts a transaction ID from an encoded transactional receipt.
     *
     * This method is for older-style individual transaction receipts and uses
     * regular expressions to find the transaction identifier. It performs no validation.
     *
     * @param string $transactionReceipt The unmodified transaction receipt string.
     * @return string|null A transaction ID, or null if not found.
     */
    public static function extractTransactionIdFromTransactionReceipt(string $transactionReceipt): ?string
    {
        $decoded = base64_decode($transactionReceipt);
        if (!$decoded || !preg_match('/"purchase-info"\s+=\s+"([^\"]+)";/', $decoded, $matches)) {
            return null;
        }

        $purchaseInfo = base64_decode($matches[1]);
        if (!$purchaseInfo || !preg_match('/"transaction-id"\s+=\s+"([^\"]+)";/', $purchaseInfo, $transactionMatches)) {
            return null;
        }

        return $transactionMatches[1];
    }

    /**
     * Decodes the outer PKCS #7 container and returns the set of receipt attributes.
     *
     * @param string $decodedReceipt
     * @return array<int, mixed>
     * @throws ValueError
     */
    private static function getReceiptAttributeSet(string $decodedReceipt): array
    {
        $decodedArray = ASN1::decodeBER($decodedReceipt);
        $sequence = $decodedArray[0]['content'] ?? null;

        if (!isset($sequence[0]['content']) || $sequence[0]['content'] !== self::PKCS7_OID) {
            throw new ValueError('Receipt is not a valid PKCS #7 container.');
        }

        $data = $sequence[1]['content'][0]['content'][2]['content'][1]['content'][0]['content'] ?? null;
        if (!is_string($data)) {
            throw new ValueError('Could not find the receipt attribute set in the payload.');
        }

        $decodedSet = ASN1::decodeBER($data);
        return $decodedSet[0]['content'] ?? [];
    }

    /**
     * Searches a set of in-app purchase attributes for a transaction identifier.
     *
     * @param string $inAppPurchaseData
     * @return string|null
     */
    private static function findTransactionIdInInAppPurchaseSet(string $inAppPurchaseData): ?string
    {
        $inAppDecoded = ASN1::decodeBER($inAppPurchaseData);
        $inAppSet = $inAppDecoded[0]['content'] ?? [];

        foreach ($inAppSet as $inAppItem) {
            $type = $inAppItem['content'][0]['content'] ?? null;
            $value = $inAppItem['content'][2]['content'] ?? null;

            if ((string)$type === (string)self::TRANSACTION_IDENTIFIER_TYPE && is_string($value)) {
                $finalDecoded = ASN1::decodeBER($value);
                return $finalDecoded[0]['content'] ?? null;
            }
        }

        return null;
    }
}
