<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$root = realpath(dirname(dirname(__FILE__)));
$library = "$root/library";

$path = [$library, get_include_path()];
set_include_path(implode(PATH_SEPARATOR, $path));

require_once $root.'/vendor/autoload.php';

use ReceiptValidator\GooglePlay\Validator as PlayValidator;

// google authentication
$applicationName = 'xxxxxx';
$scope = ['https://www.googleapis.com/auth/androidpublisher'];
$configLocation = 'googleapi.json';

// receipt data
$packageName = 'xxxxx';
$productId = 'xxxxx';
$purchaseToken = 'xxxxx';

$client = new \Google_Client();
$client->setApplicationName($applicationName);
$client->setAuthConfig($configLocation);
$client->setScopes($scope);

$validator = new PlayValidator(new \Google_Service_AndroidPublisher($client));

try {
    $response = $validator->setPackageName($packageName)->setProductId($productId)->setPurchaseToken($purchaseToken)->validatePurchase();
} catch (Exception $e) {
    echo 'got error = '.$e->getMessage().PHP_EOL;
}
