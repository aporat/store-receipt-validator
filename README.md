# store-receipt-validator

[![Latest Stable Version](https://img.shields.io/packagist/v/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)  [![Downloads](https://img.shields.io/packagist/dt/aporat/store-receipt-validator.svg?style=flat-square&logo=composer)](https://packagist.org/packages/aporat/store-receipt-validator)  [![Codecov](https://img.shields.io/codecov/c/github/aporat/store-receipt-validator?style=flat-square)](https://codecov.io/github/aporat/store-receipt-validator)  ![GitHub Actions](https://img.shields.io/github/actions/workflow/status/aporat/store-receipt-validator/ci.yml?style=flat-square)  [![License](https://img.shields.io/packagist/l/aporat/store-receipt-validator.svg?style=flat-square)](LICENSE)

A modern PHP library for validating in-app purchases from the Apple App Store (including legacy iTunes), Google Play and Amazon Appstore. Supports both production and sandbox environments with detailed response parsing.

---

## ✨ Features

- ✅ Apple App Store **Server API (v2)** support
- ✅ Apple iTunes **Legacy API** support (deprecated by Apple, still available here)
- ✅ Google Play **Developer API (Android Publisher v3)** support: subscriptions, one-time products, voided purchases
- ✅ Google Play **Real-time Developer Notifications** parsing (Pub/Sub envelope included)
- ✅ Amazon Appstore receipt validation
- ✅ App Store **Server Notifications v1 & v2** parsing
- ✅ Strong typing (PHP 8.4+), enums, and modern error handling
- ✅ PSR-3 compatible logging support
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
    $response = $validator->getTransactionHistory($transactionId);
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

> ℹ️ `Validator::validate()` is **deprecated** as of v9. Use `getTransactionHistory()` for paginated history, or `getTransactionInfo()` for a single signed transaction.

### 📚 Other App Store Server API endpoints

The `AppleAppStore\Validator` now covers Apple's full API surface:

| Area | Methods |
|---|---|
| Transactions | `getTransactionHistory()`, `getTransactionInfo()`, `getAppTransactionInfo()`, `finishTransaction()`, `setAppAccountToken()`, `sendConsumptionInformation()` |
| Order / refunds | `lookUpOrderId()`, `getRefundHistory()` |
| Subscriptions | `getAllSubscriptionStatuses()`, `extendSubscriptionRenewalDate()`, `extendSubscriptionRenewalDatesForAllActiveSubscribers()`, `getStatusOfSubscriptionRenewalDateExtensions()` |
| Notifications | `requestTestNotification()`, `getTestNotificationStatus()`, `getNotificationHistory()` |

Each returns a typed response object (`Transaction`, `AppTransaction`, `SubscriptionStatusResponse`, `RefundHistoryResponse`, `NotificationHistoryResponse`, …). See the [App Store Server API docs](https://developer.apple.com/documentation/appstoreserverapi) for endpoint semantics.

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

### 🤖 Google Play

Authentication uses a Google Cloud service account that has been granted access to your app in the Play Console ("Users and permissions" → invite the service account email with *View financial data* / *Manage orders*). Download its JSON key and pass the contents to the validator. Tokens are minted with the OAuth 2.0 JWT bearer flow and cached in memory; no extra Google SDK is required.

```php
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\Validator as GooglePlayValidator;

$validator = new GooglePlayValidator(
    packageName: 'com.example.app',
    credentials: file_get_contents('/path/to/service-account.json'),
);

try {
    // The purchase token from BillingClient's Purchase.getPurchaseToken()
    $purchase = $validator->getSubscriptionPurchaseV2($purchaseToken);
} catch (ValidationException $e) {
    echo 'Validation failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo 'State: ' . $purchase->getSubscriptionState()->name . PHP_EOL;
echo 'Entitled: ' . ($purchase->isEntitled() ? 'yes' : 'no') . PHP_EOL;
echo 'Expires: ' . $purchase->getExpiryTime()?->toIso8601String() . PHP_EOL;
echo 'Test purchase: ' . ($purchase->isTestPurchase() ? 'yes' : 'no') . PHP_EOL; // Environment::SANDBOX
echo 'Obfuscated account ID: ' . $purchase->getObfuscatedExternalAccountId() . PHP_EOL;

foreach ($purchase->getLineItems() as $item) {
    echo 'Product ID: ' . $item->getProductId() . PHP_EOL;
    echo 'Base plan: ' . $item->getBasePlanId() . PHP_EOL;
    echo 'Order ID: ' . $item->getLatestSuccessfulOrderId() . PHP_EOL;
    echo 'Auto-renewing: ' . ($item->isAutoRenewEnabled() ? 'yes' : 'no') . PHP_EOL;
}
```

> ℹ️ Google has no sandbox endpoint. Licence-tester purchases come back from the production API with a `testPurchase` marker, which the response exposes as `isTestPurchase()` and `Environment::SANDBOX`.

#### Other Google Play endpoints

| Area | Methods |
|---|---|
| Subscriptions | `getSubscriptionPurchaseV2()`, `acknowledgeSubscription()`, `revokeSubscription()` |
| One-time products | `getProductPurchase()`, `acknowledgeProduct()`, `consumeProduct()` |
| Refunds | `getVoidedPurchases()` |

```php
use ReceiptValidator\GooglePlay\RevocationContext;
use ReceiptValidator\GooglePlay\VoidedPurchasesParams;
use ReceiptValidator\GooglePlay\VoidedPurchaseType;

$product = $validator->getProductPurchase('com.example.coins.100', $purchaseToken);
if ($product->isPurchased() && !$product->isAcknowledged()) {
    $validator->acknowledgeProduct('com.example.coins.100', $purchaseToken);
}

$validator->revokeSubscription($purchaseToken, RevocationContext::proratedRefund());

$voided = $validator->getVoidedPurchases(new VoidedPurchasesParams(type: VoidedPurchaseType::INCLUDE_SUBSCRIPTIONS));
foreach ($voided->getVoidedPurchases() as $refund) {
    echo $refund->getOrderId() . ' voided at ' . $refund->getVoidedTime()?->toIso8601String() . PHP_EOL;
}
```

#### Bringing your own access tokens

If you already use `google/auth` (or want to share a token cache), implement `GooglePlay\JWT\AccessTokenProvider` or wrap a callable:

```php
use Google\Auth\Credentials\ServiceAccountCredentials;
use ReceiptValidator\GooglePlay\JWT\CallbackAccessTokenProvider;

$credentials = new ServiceAccountCredentials(
    'https://www.googleapis.com/auth/androidpublisher',
    '/path/to/service-account.json'
);

$validator = new GooglePlayValidator('com.example.app');
$validator->setAccessTokenProvider(
    new CallbackAccessTokenProvider(fn () => $credentials->fetchAuthToken()['access_token'])
);
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

## 📋 Logging

All validators support [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logging via `setLogger()`. By default, a `NullLogger` is used so no output is produced unless you inject a logger.

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('receipt-validator');
$logger->pushHandler(new StreamHandler('php://stdout'));

$validator = new AppleValidator($signingKey, $keyId, $issuerId, $bundleId);
$validator->setLogger($logger);
```

The method returns `$this` for fluent chaining:

```php
$response = $validator
    ->setLogger($logger)
    ->getTransactionHistory($transactionId);
```

### Log levels

| Level | Events |
|-------|--------|
| `DEBUG` | Outgoing API request details (environment, URI, parameters) |
| `INFO` | Successful responses; environment retries (e.g. production → sandbox) |
| `WARNING` | API error responses, unexpected HTTP status codes |
| `ERROR` | Network/connection failures |

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

## 🤖 Google Play Real-time Developer Notifications

Play publishes notifications to a Cloud Pub/Sub topic; a push subscription POSTs them to your endpoint wrapped in a Pub/Sub envelope. `ServerNotification::fromPubSubMessage()` unwraps the envelope and decodes the notification.

Unlike Apple's notifications, the payload is **not signed and carries no purchase data**: it only tells you which purchase token changed. Always re-read the purchase from the API before changing entitlement, and authenticate the push itself (Pub/Sub's OIDC bearer token) at the HTTP layer.

```php
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\ServerNotification;
use ReceiptValidator\GooglePlay\SubscriptionNotificationType;

public function googlePlay(Request $request): JsonResponse {
    try {
        $notification = ServerNotification::fromPubSubMessage($request->all());
    } catch (ValidationException $e) {
        // Undecodable messages will never succeed: acknowledge them so Pub/Sub stops retrying.
        return response()->json(['status' => 'ignored']);
    }

    if ($notification->isTestNotification()) {
        return response()->json(['status' => 'test']);
    }

    if ($sub = $notification->getSubscriptionNotification()) {
        echo 'Type: ' . $sub->getNotificationType()->name . PHP_EOL;
        echo 'Product: ' . $sub->getSubscriptionId() . PHP_EOL;

        $purchase = $validator->getSubscriptionPurchaseV2($sub->getPurchaseToken());

        if ($sub->getNotificationType()->revokesEntitlement() || !$purchase->isEntitled()) {
            // remove access
        }
    }

    if ($voided = $notification->getVoidedPurchaseNotification()) {
        echo 'Refunded order: ' . $voided->getOrderId() . PHP_EOL;
    }

    return response()->json(['status' => 'handled']);
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
