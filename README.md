store-receipt-validator
=======

[![Latest Stable Version](https://poser.pugx.org/aporat/store-receipt-validator/version.png)](https://packagist.org/packages/aporat/store-receipt-validator)
[![Composer Downloads](https://poser.pugx.org/aporat/store-receipt-validator/d/total.png)](https://packagist.org/packages/aporat/store-receipt-validator)
[![Build Status](https://github.com/aporat/store-receipt-validator/workflows/Tests/badge.svg)](https://github.com/aporat/store-receipt-validator/actions)
[![Code Coverage](https://scrutinizer-ci.com/g/aporat/store-receipt-validator/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/aporat/store-receipt-validator/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/aporat/store-receipt-validator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/aporat/store-receipt-validator/?branch=master)
[![StyleCI](https://github.styleci.io/repos/14928361/shield?branch=master)](https://github.styleci.io/repos/14928361)
[![License](https://poser.pugx.org/aporat/store-receipt-validator/license.svg)](https://packagist.org/packages/aporat/store-receipt-validator)

PHP receipt validator for Apple iTunes, Google Play and Amazon App Store

## Requirements ##

* PHP >= 7.2

## Installation ##

 `composer require aporat/store-receipt-validator`

## Quick Example ##

### iTunes ###

```php

use ReceiptValidator\iTunes\Validator as iTunesValidator;

$validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION); // Or iTunesValidator::ENDPOINT_SANDBOX if sandbox testing

$receiptBase64Data = 'ewoJInNpZ25hdHVyZSIgPSAiQXBNVUJDODZBbHpOaWtWNVl0clpBTWlKUWJLOEVkZVhrNjNrV0JBWHpsQzhkWEd1anE0N1puSVlLb0ZFMW9OL0ZTOGNYbEZmcDlZWHQ5aU1CZEwyNTBsUlJtaU5HYnloaXRyeVlWQVFvcmkzMlc5YVIwVDhML2FZVkJkZlcrT3kvUXlQWkVtb05LeGhudDJXTlNVRG9VaFo4Wis0cFA3MHBlNWtVUWxiZElWaEFBQURWekNDQTFNd2dnSTdvQU1DQVFJQ0NHVVVrVTNaV0FTMU1BMEdDU3FHU0liM0RRRUJCUVVBTUg4eEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURXpNREVHQTFVRUF3d3FRWEJ3YkdVZ2FWUjFibVZ6SUZOMGIzSmxJRU5sY25ScFptbGpZWFJwYjI0Z1FYVjBhRzl5YVhSNU1CNFhEVEE1TURZeE5USXlNRFUxTmxvWERURTBNRFl4TkRJeU1EVTFObG93WkRFak1DRUdBMVVFQXd3YVVIVnlZMmhoYzJWU1pXTmxhWEIwUTJWeWRHbG1hV05oZEdVeEd6QVpCZ05WQkFzTUVrRndjR3hsSUdsVWRXNWxjeUJUZEc5eVpURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd2daOHdEUVlKS29aSWh2Y05BUUVCQlFBRGdZMEFNSUdKQW9HQkFNclJqRjJjdDRJclNkaVRDaGFJMGc4cHd2L2NtSHM4cC9Sd1YvcnQvOTFYS1ZoTmw0WElCaW1LalFRTmZnSHNEczZ5anUrK0RyS0pFN3VLc3BoTWRkS1lmRkU1ckdYc0FkQkVqQndSSXhleFRldngzSExFRkdBdDFtb0t4NTA5ZGh4dGlJZERnSnYyWWFWczQ5QjB1SnZOZHk2U01xTk5MSHNETHpEUzlvWkhBZ01CQUFHamNqQndNQXdHQTFVZEV3RUIvd1FDTUFBd0h3WURWUjBqQkJnd0ZvQVVOaDNvNHAyQzBnRVl0VEpyRHRkREM1RllRem93RGdZRFZSMFBBUUgvQkFRREFnZUFNQjBHQTFVZERnUVdCQlNwZzRQeUdVakZQaEpYQ0JUTXphTittVjhrOVRBUUJnb3Foa2lHOTJOa0JnVUJCQUlGQURBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQUVhU2JQanRtTjRDL0lCM1FFcEszMlJ4YWNDRFhkVlhBZVZSZVM1RmFaeGMrdDg4cFFQOTNCaUF4dmRXLzNlVFNNR1k1RmJlQVlMM2V0cVA1Z204d3JGb2pYMGlreVZSU3RRKy9BUTBLRWp0cUIwN2tMczlRVWU4Y3pSOFVHZmRNMUV1bVYvVWd2RGQ0TndOWXhMUU1nNFdUUWZna1FRVnk4R1had1ZIZ2JFL1VDNlk3MDUzcEdYQms1MU5QTTN3b3hoZDNnU1JMdlhqK2xvSHNTdGNURXFlOXBCRHBtRzUrc2s0dHcrR0szR01lRU41LytlMVFUOW5wL0tsMW5qK2FCdzdDMHhzeTBiRm5hQWQxY1NTNnhkb3J5L0NVdk02Z3RLc21uT09kcVRlc2JwMGJzOHNuNldxczBDOWRnY3hSSHVPTVoydG04bnBMVW03YXJnT1N6UT09IjsKCSJwdXJjaGFzZS1pbmZvIiA9ICJld29KSW05eWFXZHBibUZzTFhCMWNtTm9ZWE5sTFdSaGRHVXRjSE4wSWlBOUlDSXlNREV5TFRBMExUTXdJREE0T2pBMU9qVTFJRUZ0WlhKcFkyRXZURzl6WDBGdVoyVnNaWE1pT3dvSkltOXlhV2RwYm1Gc0xYUnlZVzV6WVdOMGFXOXVMV2xrSWlBOUlDSXhNREF3TURBd01EUTJNVGM0T0RFM0lqc0tDU0ppZG5KeklpQTlJQ0l5TURFeU1EUXlOeUk3Q2draWRISmhibk5oWTNScGIyNHRhV1FpSUQwZ0lqRXdNREF3TURBd05EWXhOemc0TVRjaU93b0pJbkYxWVc1MGFYUjVJaUE5SUNJeElqc0tDU0p2Y21sbmFXNWhiQzF3ZFhKamFHRnpaUzFrWVhSbExXMXpJaUE5SUNJeE16TTFOems0TXpVMU9EWTRJanNLQ1NKd2NtOWtkV04wTFdsa0lpQTlJQ0pqYjIwdWJXbHVaRzF2WW1Gd2NDNWtiM2R1Ykc5aFpDSTdDZ2tpYVhSbGJTMXBaQ0lnUFNBaU5USXhNVEk1T0RFeUlqc0tDU0ppYVdRaUlEMGdJbU52YlM1dGFXNWtiVzlpWVhCd0xrMXBibVJOYjJJaU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFek16VTNPVGd6TlRVNE5qZ2lPd29KSW5CMWNtTm9ZWE5sTFdSaGRHVWlJRDBnSWpJd01USXRNRFF0TXpBZ01UVTZNRFU2TlRVZ1JYUmpMMGROVkNJN0Nna2ljSFZ5WTJoaGMyVXRaR0YwWlMxd2MzUWlJRDBnSWpJd01USXRNRFF0TXpBZ01EZzZNRFU2TlRVZ1FXMWxjbWxqWVM5TWIzTmZRVzVuWld4bGN5STdDZ2tpYjNKcFoybHVZV3d0Y0hWeVkyaGhjMlV0WkdGMFpTSWdQU0FpTWpBeE1pMHdOQzB6TUNBeE5Ub3dOVG8xTlNCRmRHTXZSMDFVSWpzS2ZRPT0iOwoJImVudmlyb25tZW50IiA9ICJTYW5kYm94IjsKCSJwb2QiID0gIjEwMCI7Cgkic2lnbmluZy1zdGF0dXMiID0gIjAiOwp9';

try {
  $response = $validator->setReceiptData($receiptBase64Data)->validate();
  // $sharedSecret = '1234...'; // Generated in iTunes Connect's In-App Purchase menu
  // $response = $validator->setSharedSecret($sharedSecret)->setReceiptData($receiptBase64Data)->validate(); // use setSharedSecret() if for recurring subscriptions
} catch (Exception $e) {
  echo 'got error = ' . $e->getMessage() . PHP_EOL;
}

if ($response->isValid()) {
  echo 'Receipt is valid.' . PHP_EOL;
  echo 'Receipt data = ' . print_r($response->getReceipt()) . PHP_EOL;
  
  foreach ($response->getPurchases() as $purchase) {
    echo 'getProductId: ' . $purchase->getProductId() . PHP_EOL;
    echo 'getTransactionId: ' . $purchase->getTransactionId() . PHP_EOL;

    if ($purchase->getPurchaseDate() != null) {
      echo 'getPurchaseDate: ' . $purchase->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
  }
} else {
  echo 'Receipt is not valid.' . PHP_EOL;
  echo 'Receipt result code = ' . $response->getResultCode() . PHP_EOL;
}
```

### Play Store ###

Get the refresh token from [OAuth2 flow](https://developers.google.com/android-publisher/authorization).

```php
use ReceiptValidator\GooglePlay\Validator as PlayValidator;

$client = new \Google_Client();
$client->setApplicationName('...');
$client->setAuthConfig('...');
$client->setScopes('...');

$validator = new PlayValidator(new \Google_Service_AndroidPublisher($client));

try {
  $response = $validator->setPackageName('PACKAGE_NAME')
    ->setProductId('PRODUCT_ID')
    ->setPurchaseToken('PURCHASE_TOKEN')
    ->validatePurchase();
} catch (Exception $e){
  var_dump($e->getMessage());
  // example message: Error calling GET ....: (404) Product not found for this application.
}
// success
```

Or [Using a service account](https://developers.google.com/android-publisher/getting_started#using_a_service_account)

Create service account [Service Account flow](https://developers.google.com/identity/protocols/OAuth2ServiceAccount) and [guide](https://stackoverflow.com/a/24365026/1248595)

```php
$googleClient = new \Google_Client();
$googleClient->setScopes([\Google_Service_AndroidPublisher::ANDROIDPUBLISHER]);
$googleClient->setApplicationName('Your_Purchase_Validator_Name');
$googleClient->setAuthConfig($pathToServiceAccountJsonFile);

$googleAndroidPublisher = new \Google_Service_AndroidPublisher($googleClient);
$validator = new \ReceiptValidator\GooglePlay\Validator($googleAndroidPublisher);

try {
  $response = $validator->setPackageName('PACKAGE_NAME')
      ->setProductId('PRODUCT_ID')
      ->setPurchaseToken('PURCHASE_TOKEN')
      ->validateSubscription();
} catch (\Exception $e){
  var_dump($e->getMessage());
  // example message: Error calling GET ....: (404) Product not found for this application.
}
// success
```

#### Reduce the size of the google sdk ####
To reduce the size of the google sdk you can follow thoses steps on the [google documentation](https://github.com/googleapis/google-api-php-client#cleaning-up-unused-services)
```json
{
    "scripts": {
        "post-install-cmd": [
            "Google_Task_Composer::cleanup"
        ],
        "post-update-cmd": [
            "Google_Task_Composer::cleanup"
        ]
    },
    "extra": {
        "google/apiclient-services": [
            "AndroidPublisher"
        ]
    }
}
```
**IMPORTANT:** If you add any services back in composer.json, you will need to remove the vendor/google/apiclient-services directory explicity for the change you made to have effect:
```sh
rm -r vendor/google/apiclient-services
composer update
```

### Amazon App Store ###

```php
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Amazon\Response as ValidatorResponse;

$validator = new AmazonValidator;

$response = null;
try {
  $response = $validator->setDeveloperSecret("DEVELOPER_SECRET")->setReceiptId("RECEIPT_ID")->setUserId("USER_ID")->validate();

} catch (Exception $e) {
  echo 'got error = ' . $e->getMessage() . PHP_EOL;
}

if ($response instanceof ValidatorResponse && $response->isValid()) {

  echo 'Receipt is valid.' . PHP_EOL;


  foreach ($response->getPurchases() as $purchase) {
    echo 'getProductId: ' . $purchase->getProductId() . PHP_EOL;

    if ($purchase->getPurchaseDate() != null) {
      echo 'getPurchaseDate: ' . $purchase->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
  }
} else {
  echo 'Receipt is not valid.' . PHP_EOL;
  echo 'Receipt result code = ' . $response->getResultCode() . PHP_EOL;
}

```
