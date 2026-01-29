# store-receipt-validator

[![Latest Stable Version](https://img.shields.io/packagist/v/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)  [![Downloads](https://img.shields.io/packagist/dt/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)  [![Codecov](https://img.shields.io/codecov/c/github/aporat/store-receipt-validator?style=flat-square)](https://codecov.io/github/aporat/store-receipt-validator)  ![GitHub Actions](https://img.shields.io/github/actions/workflow/status/aporat/store-receipt-validator/ci.yml?style=flat-square)  [![License](https://img.shields.io/packagist/l/aporat/store-receipt-validator.svg?style=flat-square)](LICENSE)

A modern PHP library for validating in-app purchase receipts from the Apple App Store (including legacy iTunes) and Amazon Appstore. Supports both production and sandbox environments with detailed response parsing.

---

## ✨ Features

- ✅ Apple App Store **Server API (v2)** support
- ✅ Apple iTunes **Legacy API** support (deprecated by Apple, still available here)
- ✅ Amazon Appstore receipt validation
- ✅ App Store **Server Notifications v1 & v2** parsing
- ✅ Strong typing (PHP 8.4+), enums, and modern error handling
- ✅ Built-in test suite with 100% coverage

---

## 📦 Requirements

- PHP >= 8.4

---

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

// 🔑 Tip: Apple's Server API does not accept the full app receipt.
// Use ReceiptUtility to extract the latest transaction ID.
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
} catch (ValidationException $e) {
    if ($e->getCode() === APIError::INVALID_TRANSACTION_ID) {
        echo "Invalid Transaction ID: {$e->getMessage()}" . PHP_EOL;
    } else {
        echo "Validation failed: {$e->getMessage()}" . PHP_EOL;
    }

    exit(1);
} catch (Exception $e) {
    echo 'Error validating transaction: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'Validation successful.' . PHP_EOL;
echo 'Bundle ID: ' . $response->getBundleId() . PHP_EOL;
echo 'App Apple ID: ' . $response->getAppAppleId() . PHP_EOL;

foreach ($response->getTransactions() as $transaction) {
    echo 'Product ID: ' . $transaction->getProductId() . PHP_EOL;
    echo 'Transaction ID: ' . $transaction->getTransactionId() . PHP_EOL;

    if ($transaction->getPurchaseDate() !== null) {
        echo 'Purchase Date: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
}
```

### 🍏 Apple iTunes (Legacy API - Deprecated)

```php
use ReceiptValidator\Environment;
use ReceiptValidator\iTunes\Validator as iTunesValidator;

$validator = new ITunesValidator($sharedSecret, Environment::PRODUCTION);

try {
    $response = $validator->setReceiptData('BASE64_RECEIPT')->validate();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit;
}

echo 'Bundle ID: ' . $response->getBundleId() . PHP_EOL;
echo 'Original Purchase Date: ' . $response->getOriginalPurchaseDate()?->toIso8601String() . PHP_EOL;

foreach ($response->getTransactions() as $tx) {
    echo 'Product ID: ' . $tx->getProductId() . PHP_EOL;
    echo 'Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;
    echo 'Original Transaction ID: ' . ($tx->getOriginalTransactionId() ?? 'N/A') . PHP_EOL;

    if ($tx->getPurchaseDate() !== null) {
        echo 'Purchase Date: ' . $tx->getPurchaseDate()?->toIso8601String() . PHP_EOL;
    }
    if ($tx->getExpiresDate() !== null) {
        echo 'Expires Date: ' . $tx->getExpiresDate()?->toIso8601String() . PHP_EOL;
    }
}

foreach ($response->getLatestReceiptInfo() as $tx) {
    echo 'Latest — Product ID: ' . $tx->getProductId() . PHP_EOL;
    echo 'Latest — Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;

    if ($tx->getPurchaseDate() !== null) {
        echo 'Latest — Purchase Date: ' . $tx->getPurchaseDate()?->toIso8601String() . PHP_EOL;
    }
    if ($tx->getExpiresDate() !== null) {
        echo 'Latest — Expires Date: ' . $tx->getExpiresDate()?->toIso8601String() . PHP_EOL;
    }
}
```

### 🛒 Amazon Appstore

```php
use ReceiptValidator\Amazon\Validator;

$validator = new Validator();

try {
    $response = $validator
        ->setDeveloperSecret('SECRET')
        ->setReceiptId('RECEIPT_ID')
        ->setUserId('USER_ID')
        ->validate();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit;
}

echo 'Receipt is valid.' . PHP_EOL;

foreach ($response->getTransactions() as $transaction) {
    echo 'Product ID: ' . $transaction->getProductId() . PHP_EOL;

    if ($transaction->getPurchaseDate() !== null) {
        echo 'Purchase Date: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
    }
}
```

---

## 📬 Apple App Store Server Notifications

### 🔔 V2 Notifications (App Store Server API)

```php
use ReceiptValidator\AppleAppStore\ServerNotification;
use ReceiptValidator\Exceptions\ValidationException;

public function subscriptions(Request $request): JsonResponse {
    try {
        $notification = new ServerNotification($request->all());

        echo 'Type: ' . $notification->getNotificationType()->value . PHP_EOL;
        echo 'Subtype: ' . ($notification->getSubtype()?->value ?? 'N/A') . PHP_EOL;
        echo 'Bundle ID: ' . $notification->getBundleId() . PHP_EOL;

        $tx = $notification->getTransaction();
        if ($tx !== null) {
            echo 'Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;
        }

        $renewalInfo = $notification->getRenewalInfo();
        if ($renewalInfo !== null) {
            echo 'Auto-Renew Product ID: ' . $renewalInfo->getAutoRenewProductId() . PHP_EOL;
        }
    } catch (ValidationException $e) {
        echo 'Invalid notification: ' . $e->getMessage() . PHP_EOL;
    }
}
```

### 🔔 V1 Notifications (iTunes - Deprecated)

```php
use ReceiptValidator\iTunes\ServerNotification;
use ReceiptValidator\Exceptions\ValidationException;

public function subscriptions(Request $request): JsonResponse {
    $sharedSecret = 'your_shared_secret';

    try {
        $notification = new ServerNotification($request->all(), $sharedSecret);

        echo 'Type: ' . $notification->getNotificationType()->value . PHP_EOL;
        echo 'Bundle ID: ' . $notification->getBundleId() . PHP_EOL;

        $transactions = $notification->getLatestReceipt()->getTransactions();

        foreach ($transactions as $tx) {
            echo 'Transaction ID: ' . $tx->getTransactionId() . PHP_EOL;
        }
    } catch (ValidationException $e) {
        echo 'Invalid notification: ' . $e->getMessage() . PHP_EOL;
    }
}
```

---

## 🧪 Testing

```bash
composer test        # Run tests with PHPUnit
composer lint        # Run code style checks with PHP_CodeSniffer
composer analyze     # Run static analysis with PHPStan
```

---

## 🙌 Contributing

Contributions are welcome!  
To get started:
1. Fork this repo
2. Create a feature branch
3. Submit a pull request

Found a bug or want a new feature? [Open an issue](https://github.com/aporat/store-receipt-validator/issues)

---

## 📄 License

Apache-2.0 License. See [LICENSE](LICENSE).

---
