
# store-receipt-validator

[![Latest Stable Version](https://img.shields.io/packagist/v/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)
[![Monthly Downloads](https://img.shields.io/packagist/dm/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)
[![Codecov](https://img.shields.io/codecov/c/github/aporat/store-receipt-validator?style=flat-square)](https://codecov.io/github/aporat/store-receipt-validator)
![GitHub Actions](https://img.shields.io/github/actions/workflow/status/aporat/store-receipt-validator/ci.yml?style=flat-square)
[![License](https://img.shields.io/packagist/l/aporat/store-receipt-validator.svg?style=flat-square)](LICENSE)

A modern PHP library for validating in-app purchase receipts from Apple iTunes, Google Play, and Amazon App Store. Supports production and sandbox environments with detailed response parsing.

---

## 📦 Requirements

- PHP >= 8.3

## 📥 Installation

```bash
composer require aporat/store-receipt-validator
```

---

## 🚀 Quick Start

### 🍏 Apple iTunes

```php
use ReceiptValidator\iTunes\Validator as iTunesValidator;

$validator = new iTunesValidator(iTunesValidator::ENDPOINT_PRODUCTION);

try {
    $response = $validator
        ->setReceiptData($receiptBase64Data)
        ->validate();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

if ($response->isValid()) {
    echo "Receipt is valid." . PHP_EOL;
    foreach ($response->getPurchases() as $purchase) {
        echo 'Product ID: ' . $purchase->getProductId() . PHP_EOL;
        echo 'Transaction ID: ' . $purchase->getTransactionId() . PHP_EOL;
    }
} else {
    echo "Invalid receipt. Result code: " . $response->getResultCode();
}
```

---

### 🤖 Google Play

#### Using OAuth2 Flow

```php
$client = new \Google_Client();
$client->setApplicationName('App Name');
$client->setAuthConfig('path/to/credentials.json');
$client->setScopes([\Google\Service\AndroidPublisher::ANDROIDPUBLISHER]);

$validator = new \ReceiptValidator\GooglePlay\Validator(new \Google\Service\AndroidPublisher($client));

$response = $validator
    ->setPackageName('PACKAGE_NAME')
    ->setProductId('PRODUCT_ID')
    ->setPurchaseToken('PURCHASE_TOKEN')
    ->validatePurchase();
```

#### Using Service Account

```php
$googleClient = new \Google_Client();
$googleClient->setApplicationName('Validator Name');
$googleClient->setAuthConfig('service-account.json');
$googleClient->setScopes([\Google\Service\AndroidPublisher::ANDROIDPUBLISHER]);

$publisher = new \Google\Service\AndroidPublisher($googleClient);
$validator = new \ReceiptValidator\GooglePlay\Validator($publisher);

$response = $validator
    ->setPackageName('PACKAGE_NAME')
    ->setProductId('PRODUCT_ID')
    ->setPurchaseToken('PURCHASE_TOKEN')
    ->validateSubscription();
```

✅ *To reduce Google SDK bloat, follow [this guide](https://github.com/googleapis/google-api-php-client#cleaning-up-unused-services).*

---

### 🛒 Amazon App Store

```php
use ReceiptValidator\Amazon\Validator;
use ReceiptValidator\Amazon\Response;

$validator = new Validator();

$response = $validator
    ->setDeveloperSecret("SECRET")
    ->setReceiptId("RECEIPT_ID")
    ->setUserId("USER_ID")
    ->validate();

if ($response instanceof Response && $response->isValid()) {
    echo "Receipt is valid.";
} else {
    echo "Invalid receipt. Code: " . $response->getResultCode();
}
```

---

## 🧪 Testing

```bash
composer test
composer check  # Run code style checks (PHP_CodeSniffer)
composer analyze  # Run static analysis (PHPStan)
```

---

## 🙌 Contributing

Contributions are welcome! Feel free to:
- Fork this repo
- Create a feature branch
- Submit a pull request

Found a bug or want a new feature? [Open an issue](https://github.com/aporat/store-receipt-validator/issues)
