<?php

namespace ReceiptValidator\AppleAppStore;

class ReceiptUtility
{
    public static function extractTransactionIdFromTransactionReceipt(string $transactionReceipt): ?string
    {
        $decoded = base64_decode($transactionReceipt);
        if (!$decoded) {
            return null;
        }

        if (!preg_match('/"purchase-info"\\s+=\\s+"([^"]+)";/', $decoded, $matches)) {
            return null;
        }

        $purchaseInfo = base64_decode($matches[1]);
        if (!preg_match('/"transaction-id"\\s+=\\s+"([^"]+)";/', $purchaseInfo, $transactionMatches)) {
            return null;
        }

        return $transactionMatches[1];
    }
}
