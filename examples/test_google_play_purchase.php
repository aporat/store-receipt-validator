<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\Validator as GooglePlayValidator;

// A Google Cloud service account JSON key with access to the app in the Play Console.
$serviceAccountJson = file_get_contents(__DIR__ . '/google-play-service-account.json');
$packageName        = 'com.example.app';
$purchaseToken      = '...'; // Purchase.getPurchaseToken() from the Android billing client

$validator = new GooglePlayValidator($packageName, $serviceAccountJson);

try {
    $purchase = $validator->getSubscriptionPurchaseV2($purchaseToken);
} catch (ValidationException $e) {
    echo 'Validation failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Subscription state: ' . $purchase->getSubscriptionState()->name . PHP_EOL;
echo 'Entitled: ' . ($purchase->isEntitled() ? 'yes' : 'no') . PHP_EOL;
echo 'Test purchase: ' . ($purchase->isTestPurchase() ? 'yes' : 'no') . PHP_EOL;
echo 'Latest order ID: ' . $purchase->getLatestOrderId() . PHP_EOL;

foreach ($purchase->getLineItems() as $item) {
    echo 'Product ID: ' . $item->getProductId() . PHP_EOL;
    echo 'Base plan: ' . ($item->getBasePlanId() ?? 'n/a') . PHP_EOL;
    echo 'Auto-renewing: ' . ($item->isAutoRenewEnabled() ? 'yes' : 'no') . PHP_EOL;

    if ($item->getExpiryTime() !== null) {
        echo 'Expires: ' . $item->getExpiryTime()->toIso8601String() . PHP_EOL;
    }
}
