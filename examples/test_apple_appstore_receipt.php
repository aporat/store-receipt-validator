<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ReceiptValidator\AppleAppStore\APIError;
use ReceiptValidator\AppleAppStore\ReceiptUtility;
use ReceiptValidator\AppleAppStore\Validator as AppleValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    throw new RuntimeException('Unable to resolve project root.');
}

$signingKeyPath = $root . '/examples/SubscriptionKey_RA9DAYVX3X.p8';
$signingKey = file_get_contents($signingKeyPath);
if ($signingKey === false) {
    throw new RuntimeException("Failed to read signing key at: {$signingKeyPath}");
}

$keyId    = 'RA9DAYVX3X';
$issuerId = '69a6de82-xxxx-xxxx-xxxx-xxxxxxxx';
$bundleId = 'com.myspp';

// Base64 App Receipt (PKCS7)
$receiptBase64Data = 'xxxx=';

$transactionId = ReceiptUtility::extractTransactionIdFromAppReceipt($receiptBase64Data);

$validator = new AppleValidator(
    signingKey: $signingKey,
    keyId: $keyId,
    issuerId: $issuerId,
    bundleId: $bundleId,
    environment: Environment::PRODUCTION
);

try {
    $response = $validator
        ->setTransactionId($transactionId)
        ->validate();
} catch (ValidationException $e) {
    if ($e->getCode() === APIError::INVALID_TRANSACTION_ID) {
        echo 'API failed (invalid transaction id): ' . $e->getMessage() . PHP_EOL;
    } else {
        echo 'Validation failed: ' . $e->getMessage() . PHP_EOL;
    }
    exit(1);
} catch (Throwable $e) {
    echo 'Unexpected error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Validation successful.' . PHP_EOL;
echo 'Bundle ID: ' . $response->getBundleId() . PHP_EOL;
echo 'App Apple ID: ' . ($response->getAppAppleId() ?? 'N/A') . PHP_EOL;

foreach ($response->getTransactions() as $tx) {
    echo 'Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;
    echo 'Original Transaction ID: ' . ($tx->getOriginalTransactionId() ?? 'N/A') . PHP_EOL;
    echo 'Product ID: ' . ($tx->getProductId() ?? 'N/A') . PHP_EOL;

    if ($tx->getPurchaseDate() !== null) {
        echo 'Purchase Date: ' . $tx->getPurchaseDate()?->toIso8601String() . PHP_EOL;
    }
    if ($tx->getExpiresDate() !== null) {
        echo 'Expires Date: ' . $tx->getExpiresDate()?->toIso8601String() . PHP_EOL;
    }
}
