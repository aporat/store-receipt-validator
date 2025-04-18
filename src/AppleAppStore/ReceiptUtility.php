<?php

namespace ReceiptValidator\AppleAppStore;

use phpseclib3\File\ASN1;
use ValueError;

class ReceiptUtility
{
    private const string PKCS7_OID = '1.2.840.113549.1.7.2';
    private const int IN_APP_ARRAY = 17;
    private const int TRANSACTION_IDENTIFIER = 1703;

    /**
     * Extracts a transaction id from an encoded App Receipt.
     * Throws if the receipt does not match the expected format.
     * *NO validation* is performed on the receipt, and any data returned
     * should only be used to call the App Store Server API.
     *
     * @param string $appReceipt The unmodified app receipt
     * @return string|null A transaction id from the array of in-app purchases,
     *                     or null if the receipt contains no in-app purchases
     * @throws ValueError
     */
    public static function extractTransactionIdFromAppReceipt(string $appReceipt): ?string
    {
        $decodedArray = ASN1::decodeBER(base64_decode($appReceipt));
        $sequence = $decodedArray[0]['content'] ?? null;

        if (!isset($sequence[0]['content']) || $sequence[0]['content'] !== self::PKCS7_OID) {
            throw new ValueError('Invalid PKCS7 OID');
        }

        $data = $sequence[1]['content'][0]['content'][2]['content'][1]['content'][0]['content'] ?? null;
        if (!is_string($data)) {
            throw new ValueError('Invalid inner content');
        }

        $decodedSet = ASN1::decodeBER($data);
        $receiptSet = $decodedSet[0]['content'] ?? [];

        foreach ($receiptSet as $entry) {
            $type = $entry['content'][0]['content'] ?? null;
            $value = $entry['content'][2]['content'] ?? null;

            if ((string)$type === (string)self::IN_APP_ARRAY && is_string($value)) {
                $inAppDecoded = ASN1::decodeBER($value);
                $inAppSet = $inAppDecoded[0]['content'] ?? [];

                foreach ($inAppSet as $inAppItem) {
                    $type = $inAppItem['content'][0]['content'] ?? null;
                    $value = $inAppItem['content'][2]['content'] ?? null;

                    if ((string)$type === (string)self::TRANSACTION_IDENTIFIER && is_string($value)) {
                        $finalDecoded = ASN1::decodeBER($value);
                        return $finalDecoded[0]['content'] ?? null;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extracts a transaction id from an encoded transactional receipt.
     * Throws if the receipt does not match the expected format.
     * *NO validation* is performed on the receipt,
     * and any data returned should only be used to call the App Store Server API.
     *
     * @param string $transactionReceipt The unmodified transactionReceipt
     * @return string|null A transaction id, or null if no transactionId is found in the receipt
     */
    public static function extractTransactionIdFromTransactionReceipt(string $transactionReceipt): ?string
    {
        $decoded = base64_decode($transactionReceipt);
        if (!$decoded || !preg_match('/"purchase-info"\s+=\s+"([^\"]+)";/', $decoded, $matches)) {
            return null;
        }

        $purchaseInfo = base64_decode($matches[1]);
        if (!preg_match('/"transaction-id"\s+=\s+"([^\"]+)";/', $purchaseInfo, $transactionMatches)) {
            return null;
        }

        return $transactionMatches[1];
    }
}
