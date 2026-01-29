<?php

/**
 * @deprecated This example uses the legacy iTunes validator which is deprecated.
 *             Apple has deprecated the verifyReceipt endpoint in favor of the App Store Server API.
 *             Please use the AppleAppStore\Validator instead. See test_appstore_receipt.php for an example.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Validator as ITunesValidator;

$receiptBase64Data = 'receiptBase64Data';
$secret = 'secret';

// The constructor already takes the shared secret; no need to call setSharedSecret() again.
$validator = new ITunesValidator($secret, Environment::PRODUCTION);

try {
    $response = $validator
        ->setReceiptData($receiptBase64Data) // raw JSON is auto-encoded if you pass JSON instead of base64
        ->validate();
} catch (ValidationException $e) {
    echo 'Validation failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (Throwable $e) {
    echo 'Unexpected error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Receipt is valid.' . PHP_EOL;

echo 'Bundle ID: ' . $response->getBundleId() . PHP_EOL;
echo 'Original Purchase Date: ' . $response->getOriginalPurchaseDate()?->toIso8601String() . PHP_EOL;

foreach ($response->getTransactions() as $tx) {
    echo 'Product ID: ' . $tx->getProductId() . PHP_EOL;
    echo 'Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;
    echo 'Original Transaction ID: ' . ($tx->getOriginalTransactionId() ?? 'N/A') . PHP_EOL;

    if ($tx->getPurchaseDate() !== null) {
        echo 'Purchase Date: ' . $tx->getPurchaseDate()?->toIso8601String() . PHP_EOL;
    }
    if ($tx->getExpiresDate() !== null) {
        echo 'Expires Date: ' . $tx->getExpiresDate()?->toIso8601String() . PHP_EOL;
    }
}

foreach ($response->getLatestReceiptInfo() as $tx) {
    echo 'Latest — Product ID: ' . $tx->getProductId() . PHP_EOL;
    echo 'Latest — Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;

    if ($tx->getPurchaseDate() !== null) {
        echo 'Latest — Purchase Date: ' . $tx->getPurchaseDate()?->toIso8601String() . PHP_EOL;
    }
    if ($tx->getExpiresDate() !== null) {
        echo 'Latest — Expires Date: ' . $tx->getExpiresDate()?->toIso8601String() . PHP_EOL;
    }
}
