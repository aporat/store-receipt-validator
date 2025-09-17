<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Environment;

$developerSecret = '99FD_DL23EMhrOGDnur9-ulvqomrSg6qyLPSD3CFE=';
$validator = new AmazonValidator($developerSecret, Environment::PRODUCTION);

try {
    $response = $validator->setReceiptId('q1YqVrJSSs7P1UvMTazKz9PLTCwoTswtyEktM9JLrShIzCvOzM-LL04tiTdW0lFKASo2NDEwMjCwMDM2MTC0AIqVAsUsLd1c4l18jIxdfTOK_N1d8kqLLHVLc8oK83OLgtPNCit9AoJdjJ3dXG2BGkqUrAxrAQ')->setUserId('USER_ID')->validate();
} catch (Exception $e) {
    echo 'got error = ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit;
}

echo 'Receipt is valid.' . PHP_EOL;

foreach ($response->getTransactions() as $transaction) {
    echo 'getProductId: ' . $transaction->getProductId() . PHP_EOL;

    if ($transaction->getPurchaseDate() != null) {
        echo 'getPurchaseDate: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
}

