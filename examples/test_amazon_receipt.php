<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ReceiptValidator\Amazon\APIError;
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

$developerSecret = '99FD_DL23EMhrOGDnur9-ulvqomrSg6qyLPSD3CFE=';
$receiptId       = 'q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ';
$userId          = 'USER_ID';

$validator = new AmazonValidator($developerSecret, Environment::PRODUCTION);

try {
    $response = $validator->validate($receiptId, $userId);
} catch (ValidationException $e) {
    $error = APIError::fromException($e);

    if ($error?->isCanceledReceipt()) {
        echo 'Receipt is no longer valid; revoke the content it granted.' . PHP_EOL;
    } elseif ($error?->isRetryable()) {
        echo 'Temporary failure; retry later.' . PHP_EOL;
    }

    echo 'got error = ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Receipt is valid.' . PHP_EOL;
echo 'Product ID: ' . $response->getProductId() . PHP_EOL;
echo 'Product type: ' . $response->getProductType()?->name . PHP_EOL;
echo 'Entitled: ' . ($response->isEntitled() ? 'yes' : 'no') . PHP_EOL;
echo 'Test transaction: ' . ($response->isTestTransaction() ? 'yes' : 'no') . PHP_EOL;

$transaction = $response->getTransaction();

if ($transaction?->getPurchaseDate() !== null) {
    echo 'Purchase date: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
}

if ($transaction?->isSubscription()) {
    echo 'Expires: ' . $transaction->getExpiresAt()?->toIso8601String() . PHP_EOL;
    echo 'Auto-renewing: ' . ($transaction->isAutoRenewing() ? 'yes' : 'no') . PHP_EOL;
    echo 'Term: ' . $transaction->getTerm() . PHP_EOL;
}
