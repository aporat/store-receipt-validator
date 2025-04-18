
# store-receipt-validator

[![Latest Stable Version](https://img.shields.io/packagist/v/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)
[![Monthly Downloads](https://img.shields.io/packagist/dm/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)
[![Codecov](https://img.shields.io/codecov/c/github/aporat/store-receipt-validator?style=flat-square)](https://codecov.io/github/aporat/store-receipt-validator)
![GitHub Actions](https://img.shields.io/github/actions/workflow/status/aporat/store-receipt-validator/ci.yml?style=flat-square)
[![License](https://img.shields.io/packagist/l/aporat/store-receipt-validator.svg?style=flat-square)](LICENSE)

A modern PHP library for validating in-app purchase receipts from Apple iTunes, and Amazon App Store. Supports production and sandbox environments with detailed response parsing.

---

## 📦 Requirements

- PHP >= 8.3

## 📥 Installation

```bash
composer require aporat/store-receipt-validator
```

---

## 🚀 Quick Start

### 📲 Apple App Store Server API

```php
use ReceiptValidator\AppleAppStore\ReceiptUtility;
use ReceiptValidator\AppleAppStore\Validator as AppleValidator;
use ReceiptValidator\Environment;

// Credentials
$signingKey = file_get_contents($root . '/examples/SubscriptionKey_RA9DAYVX3X.p8');
$keyId = 'RA9DAYVX3X';
$issuerId = 'xxxxxx-xxxx-xxxx-xxxx-xxxxxxx';
$bundleId = 'com.myapp';

$receiptBase64Data = '...'; // your app receipt here
$transactionId = ReceiptUtility::extractTransactionIdFromAppReceipt($receiptBase64Data);

$validator = new AppleValidator(
    signingKey: $signingKey,
    keyId: $keyId,
    issuerId: $issuerId,
    bundleId: $bundleId,
    environment: Environment::PRODUCTION
);

try {
    $response = $validator->setTransactionId($transactionId)->validate();
} catch (Exception $e) {
    echo 'Error validating transaction: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Validation successful.' . PHP_EOL;
echo 'Bundle ID: ' . $response->getBundleId() . PHP_EOL;
echo 'App Apple ID: ' . $response->getAppAppleId() . PHP_EOL;

foreach ($response->getTransactions() as $transaction) {
    echo 'getProductId: ' . $transaction->getProductId() . PHP_EOL;
    echo 'getTransactionId: ' . $transaction->getTransactionId() . PHP_EOL;

    if ($transaction->getPurchaseDate() != null) {
        echo 'getPurchaseDate: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
}
```

### 🍏 Apple iTunes (Deprecated)

```php
use ReceiptValidator\Environment;
use ReceiptValidator\iTunes\Validator as iTunesValidator;

$validator = new iTunesValidator(Environment::PRODUCTION);

try {
    $response = $validator->setSharedSecret('SHARED_SECRET')->setReceiptData('BASE64_RECEIPT')->validate();
} catch (Exception $e) {
    echo 'got error = ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit;
}

echo 'Receipt is valid.' . PHP_EOL;

echo 'getBundleId: ' . $response->getBundleId() . PHP_EOL;

foreach ($response->getPurchases() as $purchase) {
    echo 'getProductId: ' . $purchase->getProductId() . PHP_EOL;
    echo 'getTransactionId: ' . $purchase->getTransactionId() . PHP_EOL;

    if ($purchase->getPurchaseDate() != null) {
        echo 'getPurchaseDate: ' . $purchase->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
}
```

---

### 🛒 Amazon App Store

```php
use ReceiptValidator\Amazon\Validator;
use ReceiptValidator\Amazon\Response;

$validator = new Validator();

try {
    $response = $validator->setDeveloperSecret('SECRET')->setReceiptId('RECEIPT_ID')->setUserId('USER_ID')->validate();
} catch (Exception $e) {
    echo 'got error = ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit;
}

echo 'Receipt is valid.' . PHP_EOL;

foreach ($response->getPurchases() as $purchase) {
    echo 'getProductId: ' . $purchase->getProductId() . PHP_EOL;

    if ($purchase->getPurchaseDate() != null) {
        echo 'getPurchaseDate: ' . $purchase->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
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
