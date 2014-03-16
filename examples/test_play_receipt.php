<?php

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

$root = realpath(dirname(dirname(__FILE__)));
$library = "$root/library";

$path = array($library, get_include_path());
set_include_path(implode(PATH_SEPARATOR, $path));

require_once $root . '/vendor/autoload.php';

use ReceiptValidator\GooglePlay\Validator as PlayValidator;

// google authencation 
$client_id = 'xxxxxxx';
$client_secret = 'xxxx';
$refresh_token = 'xxxx';


// receipt data
$package_name = 'com.example';
$product_id = 'coins_10000';
$purchase_token = 'xxxxxx';

$validator = new PlayValidator(['client_id' => $client_id, 'client_secret' => $client_secret, 'refresh_token' => $refresh_token]);

try {
    $response = $validator->setPackageName($package_name)->setProductId($product_id)->setPurchaseToken($purchase_token)->validate();
} catch (Exception $e) {
    echo 'got error = ' . $e->getMessage() . PHP_EOL;
}

print_R($response);