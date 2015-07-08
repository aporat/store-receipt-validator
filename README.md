store-receipt-validator
=======

[![Latest Stable Version](https://poser.pugx.org/aporat/store-receipt-validator/version.png)](https://packagist.org/packages/aporat/store-receipt-validator) [![Composer Downloads](https://poser.pugx.org/aporat/store-receipt-validator/d/total.png)](https://packagist.org/packages/aporat/store-receipt-validator)

[![Build Status](https://travis-ci.org/aporat/store-receipt-validator.png?branch=master)](https://travis-ci.org/aporat/store-receipt-validator) [![Dependency Status](https://www.versioneye.com/user/projects/546f4d8c810106c2af0005b0/badge.svg?style=flat)](https://www.versioneye.com/user/projects/546f4d8c810106c2af0005b0) [![Coverage Status](https://img.shields.io/coveralls/aporat/store-receipt-validator.svg)](https://coveralls.io/r/aporat/store-receipt-validator)

PHP library that can be used to validate base64 encoded iTunes in app purchase receipts.


## Requirements ##

* PHP >= 5.4

## Getting Started ##

The easiest way to work with this package is when it's installed as a
Composer package inside your project. Composer isn't strictly
required, but makes life a lot easier.

If you're not familiar with Composer, please see <http://getcomposer.org/>.

1. Add iap-validator to your application's composer.json.

        {
            ...
            "require": {
                "aporat/store-receipt-validator": "dev-master"
            },
            ...
        }

2. Run `php composer install`.

3. If you haven't already, add the Composer autoload to your project's
   initialization file. (example)

        require 'vendor/autoload.php';


## Quick Example ##

### iTunes ###

```php

use ReceiptValidator\iTunes\Validator as iTunesValidator;

$validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);

$receiptBase64Data = 'ewoJInNpZ25hdHVyZSIgPSAiQXBNVUJDODZBbHpOaWtWNVl0clpBTWlKUWJLOEVkZVhrNjNrV0JBWHpsQzhkWEd1anE0N1puSVlLb0ZFMW9OL0ZTOGNYbEZmcDlZWHQ5aU1CZEwyNTBsUlJtaU5HYnloaXRyeVlWQVFvcmkzMlc5YVIwVDhML2FZVkJkZlcrT3kvUXlQWkVtb05LeGhudDJXTlNVRG9VaFo4Wis0cFA3MHBlNWtVUWxiZElWaEFBQURWekNDQTFNd2dnSTdvQU1DQVFJQ0NHVVVrVTNaV0FTMU1BMEdDU3FHU0liM0RRRUJCUVVBTUg4eEN6QUpCZ05WQkFZVEFsVlRNUk13RVFZRFZRUUtEQXBCY0hCc1pTQkpibU11TVNZd0pBWURWUVFMREIxQmNIQnNaU0JEWlhKMGFXWnBZMkYwYVc5dUlFRjFkR2h2Y21sMGVURXpNREVHQTFVRUF3d3FRWEJ3YkdVZ2FWUjFibVZ6SUZOMGIzSmxJRU5sY25ScFptbGpZWFJwYjI0Z1FYVjBhRzl5YVhSNU1CNFhEVEE1TURZeE5USXlNRFUxTmxvWERURTBNRFl4TkRJeU1EVTFObG93WkRFak1DRUdBMVVFQXd3YVVIVnlZMmhoYzJWU1pXTmxhWEIwUTJWeWRHbG1hV05oZEdVeEd6QVpCZ05WQkFzTUVrRndjR3hsSUdsVWRXNWxjeUJUZEc5eVpURVRNQkVHQTFVRUNnd0tRWEJ3YkdVZ1NXNWpMakVMTUFrR0ExVUVCaE1DVlZNd2daOHdEUVlKS29aSWh2Y05BUUVCQlFBRGdZMEFNSUdKQW9HQkFNclJqRjJjdDRJclNkaVRDaGFJMGc4cHd2L2NtSHM4cC9Sd1YvcnQvOTFYS1ZoTmw0WElCaW1LalFRTmZnSHNEczZ5anUrK0RyS0pFN3VLc3BoTWRkS1lmRkU1ckdYc0FkQkVqQndSSXhleFRldngzSExFRkdBdDFtb0t4NTA5ZGh4dGlJZERnSnYyWWFWczQ5QjB1SnZOZHk2U01xTk5MSHNETHpEUzlvWkhBZ01CQUFHamNqQndNQXdHQTFVZEV3RUIvd1FDTUFBd0h3WURWUjBqQkJnd0ZvQVVOaDNvNHAyQzBnRVl0VEpyRHRkREM1RllRem93RGdZRFZSMFBBUUgvQkFRREFnZUFNQjBHQTFVZERnUVdCQlNwZzRQeUdVakZQaEpYQ0JUTXphTittVjhrOVRBUUJnb3Foa2lHOTJOa0JnVUJCQUlGQURBTkJna3Foa2lHOXcwQkFRVUZBQU9DQVFFQUVhU2JQanRtTjRDL0lCM1FFcEszMlJ4YWNDRFhkVlhBZVZSZVM1RmFaeGMrdDg4cFFQOTNCaUF4dmRXLzNlVFNNR1k1RmJlQVlMM2V0cVA1Z204d3JGb2pYMGlreVZSU3RRKy9BUTBLRWp0cUIwN2tMczlRVWU4Y3pSOFVHZmRNMUV1bVYvVWd2RGQ0TndOWXhMUU1nNFdUUWZna1FRVnk4R1had1ZIZ2JFL1VDNlk3MDUzcEdYQms1MU5QTTN3b3hoZDNnU1JMdlhqK2xvSHNTdGNURXFlOXBCRHBtRzUrc2s0dHcrR0szR01lRU41LytlMVFUOW5wL0tsMW5qK2FCdzdDMHhzeTBiRm5hQWQxY1NTNnhkb3J5L0NVdk02Z3RLc21uT09kcVRlc2JwMGJzOHNuNldxczBDOWRnY3hSSHVPTVoydG04bnBMVW03YXJnT1N6UT09IjsKCSJwdXJjaGFzZS1pbmZvIiA9ICJld29KSW05eWFXZHBibUZzTFhCMWNtTm9ZWE5sTFdSaGRHVXRjSE4wSWlBOUlDSXlNREV5TFRBMExUTXdJREE0T2pBMU9qVTFJRUZ0WlhKcFkyRXZURzl6WDBGdVoyVnNaWE1pT3dvSkltOXlhV2RwYm1Gc0xYUnlZVzV6WVdOMGFXOXVMV2xrSWlBOUlDSXhNREF3TURBd01EUTJNVGM0T0RFM0lqc0tDU0ppZG5KeklpQTlJQ0l5TURFeU1EUXlOeUk3Q2draWRISmhibk5oWTNScGIyNHRhV1FpSUQwZ0lqRXdNREF3TURBd05EWXhOemc0TVRjaU93b0pJbkYxWVc1MGFYUjVJaUE5SUNJeElqc0tDU0p2Y21sbmFXNWhiQzF3ZFhKamFHRnpaUzFrWVhSbExXMXpJaUE5SUNJeE16TTFOems0TXpVMU9EWTRJanNLQ1NKd2NtOWtkV04wTFdsa0lpQTlJQ0pqYjIwdWJXbHVaRzF2WW1Gd2NDNWtiM2R1Ykc5aFpDSTdDZ2tpYVhSbGJTMXBaQ0lnUFNBaU5USXhNVEk1T0RFeUlqc0tDU0ppYVdRaUlEMGdJbU52YlM1dGFXNWtiVzlpWVhCd0xrMXBibVJOYjJJaU93b0pJbkIxY21Ob1lYTmxMV1JoZEdVdGJYTWlJRDBnSWpFek16VTNPVGd6TlRVNE5qZ2lPd29KSW5CMWNtTm9ZWE5sTFdSaGRHVWlJRDBnSWpJd01USXRNRFF0TXpBZ01UVTZNRFU2TlRVZ1JYUmpMMGROVkNJN0Nna2ljSFZ5WTJoaGMyVXRaR0YwWlMxd2MzUWlJRDBnSWpJd01USXRNRFF0TXpBZ01EZzZNRFU2TlRVZ1FXMWxjbWxqWVM5TWIzTmZRVzVuWld4bGN5STdDZ2tpYjNKcFoybHVZV3d0Y0hWeVkyaGhjMlV0WkdGMFpTSWdQU0FpTWpBeE1pMHdOQzB6TUNBeE5Ub3dOVG8xTlNCRmRHTXZSMDFVSWpzS2ZRPT0iOwoJImVudmlyb25tZW50IiA9ICJTYW5kYm94IjsKCSJwb2QiID0gIjEwMCI7Cgkic2lnbmluZy1zdGF0dXMiID0gIjAiOwp9';

try {
    $response = $validator->setReceiptData($receiptBase64Data)->validate();
} catch (Exception $e) {
    echo 'got error = ' . $e->getMessage() . PHP_EOL;
}

if ($response->isValid()) {
    echo 'Receipt is valid.' . PHP_EOL;
    echo 'Receipt data = ' . print_r($response->getReceipt()) . PHP_EOL;
} else {
    echo 'Receipt is not valid.' . PHP_EOL;
    echo 'Receipt result code = ' . $response->getResultCode() . PHP_EOL;
}
```

### Play Store ###

Get the refresh token from [OAuth2 flow](https://developers.google.com/android-publisher/authorization).

```php
use ReceiptValidator\GooglePlay\Validator as PlayValidator;
$validator = new PlayValidator([
    'client_id' => '...',
    'client_secret' => '...',
    'refresh_token' => '...'
]);

try{
    $response = $validator->setPackageName('com.example')
        ->setProductId('com.example.coins100')
        ->setPurchaseToken('....')
        ->validate();
}catch(Exception $e){
    var_dump($e->getMessage());
    // example message: Error calling GET ....: (404) Product not found for this application.
}
// success
```

Attributes contained in response are as [documented in Purchases.product](https://developers.google.com/android-publisher/api-ref/purchases/products#resource)
