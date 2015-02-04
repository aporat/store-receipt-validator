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

$receiptBase64Data = 'AluGxOuMy+RT1gkyFCoD1i1KT3KUZl+F5FAAW0ELBlCUbC9dW14876aW0OXBlNJ6pXbBBFB8K0LDy6LuoAS8iBiq3529aRbVRUSKCPeCDZ7apC2zqFYZ4N7bSFDMeb92wzN0X/dELxlkRH4bWjO67X7gnHcN47qHoVckSlGo/mpbAAADVzCCA1MwggI7oAMCAQICCGUUkU3ZWAS1MA0GCSqGSIb3DQEBBQUAMH8xCzAJBgNVBAYTAlVTMRMwEQYDVQQKDApBcHBsZSBJbmMuMSYwJAYDVQQLDB1BcHBsZSBDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eTEzMDEGA1UEAwwqQXBwbGUgaVR1bmVzIFN0b3JlIENlcnRpZmljYXRpb24gQXV0aG9yaXR5MB4XDTA5MDYxNTIyMDU1NloXDTE0MDYxNDIyMDU1NlowZDEjMCEGA1UEAwwaUHVyY2hhc2VSZWNlaXB0Q2VydGlmaWNhdGUxGzAZBgNVBAsMEkFwcGxlIGlUdW5lcyBTdG9yZTETMBEGA1UECgwKQXBwbGUgSW5jLjELMAkGA1UEBhMCVVMwgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMrRjF2ct4IrSdiTChaI0g8pwv/cmHs8p/RwV/rt/91XKVhNl4XIBimKjQQNfgHsDs6yju++DrKJE7uKsphMddKYfFE5rGXsAdBEjBwRIxexTevx3HLEFGAt1moKx509dhxtiIdDgJv2YaVs49B0uJvNdy6SMqNNLHsDLzDS9oZHAgMBAAGjcjBwMAwGA1UdEwEB/wQCMAAwHwYDVR0jBBgwFoAUNh3o4p2C0gEYtTJrDtdDC5FYQzowDgYDVR0PAQH/BAQDAgeAMB0GA1UdDgQWBBSpg4PyGUjFPhJXCBTMzaN+mV8k9TAQBgoqhkiG92NkBgUBBAIFADANBgkqhkiG9w0BAQUFAAOCAQEAEaSbPjtmN4C/IB3QEpK32RxacCDXdVXAeVReS5FaZxc+t88pQP93BiAxvdW/3eTSMGY5FbeAYL3etqP5gm8wrFojX0ikyVRStQ+/AQ0KEjtqB07kLs9QUe8czR8UGfdM1EumV/UgvDd4NwNYxLQMg4WTQfgkQQVy8GXZwVHgbE/UC6Y7053pGXBk51NPM3woxhd3gSRLvXj+loHsStcTEqe9pBDpmG5+sk4tw+GK3GMeEN5/+e1QT9np/Kl1nj+aBw7C0xsy0bFnaAd1cSS6xdory/CUvM6gtKsmnOOdqTesbp0bs8sn6Wqs0C9dgcxRHuOMZ2tm8npLUm7argOSzQ==';

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