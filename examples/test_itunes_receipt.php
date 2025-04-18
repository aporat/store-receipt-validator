<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = realpath(dirname(__FILE__, 2));
$library = "$root/library";

$path = [$library, get_include_path()];
set_include_path(implode(PATH_SEPARATOR, $path));

require_once $root . '/vendor/autoload.php';

use ReceiptValidator\Environment;
use ReceiptValidator\iTunes\Validator as iTunesValidator;

$receiptBase64Data = 'receiptBase64Data';
$secret = 'secret';

$validator = new iTunesValidator($secret, Environment::PRODUCTION);

try {
    $response = $validator->setSharedSecret($secret)->setReceiptData($receiptBase64Data)->validate();
} catch (Exception $e) {
    echo 'got error = ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit;
}

echo 'Receipt is valid.' . PHP_EOL;

echo 'getBundleId: ' . $response->getBundleId() . PHP_EOL;

foreach ($response->getTransactions() as $transaction) {
    echo 'getProductId: ' . $transaction->getProductId() . PHP_EOL;
    echo 'getTransactionId: ' . $transaction->getTransactionId() . PHP_EOL;

    if ($transaction->getPurchaseDate() != null) {
        echo 'getPurchaseDate: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
}
