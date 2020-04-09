<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$root = realpath(dirname(dirname(__FILE__)));
$library = "$root/library";

$path = [$library, get_include_path()];
set_include_path(implode(PATH_SEPARATOR, $path));

require_once $root.'/vendor/autoload.php';

use ReceiptValidator\Amazon\Response as ValidatorResponse;
use ReceiptValidator\Amazon\Validator as AmazonValidator;

$validator = new AmazonValidator();

$response = null;

try {
    $response = $validator->setDeveloperSecret('DEVELOPER_SECRET')->setReceiptId('RECEIPT_ID')->setUserId('USER_ID')->validate();
} catch (Exception $e) {
    echo 'got error = '.$e->getMessage().PHP_EOL;
}

if ($response instanceof ValidatorResponse && $response->isValid()) {
    echo 'Receipt is valid.'.PHP_EOL;

    foreach ($response->getPurchases() as $purchase) {
        echo 'getProductId: '.$purchase->getProductId().PHP_EOL;

        if ($purchase->getPurchaseDate() != null) {
            echo 'getPurchaseDate: '.$purchase->getPurchaseDate()->toIso8601String().PHP_EOL;
        }
    }
} else {
    echo 'Receipt is not valid.'.PHP_EOL;
    echo 'Receipt result code = '.$response->getResultCode().PHP_EOL;
}
