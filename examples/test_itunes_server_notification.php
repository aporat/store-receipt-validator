<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = realpath(dirname(__FILE__, 2));
$library = "$root/library";

$path = [$library, get_include_path()];
set_include_path(implode(PATH_SEPARATOR, $path));

require_once $root . '/vendor/autoload.php';

use ReceiptValidator\iTunes\ServerNotification;
use ReceiptValidator\Exceptions\ValidationException;

$data = [
    'notification_type' => 'DID_RENEW',
    'password' => 'dummy_shared_secret',
    'environment' => 'PROD',
    'auto_renew_product_id' => 'my.app.plan.monthly',
    'auto_renew_status' => 'true',
    'unified_receipt' => [
        'status' => 0,
        'environment' => 'Production',
        'latest_receipt_info' => [
            [
                'quantity' => '1',
                'product_id' => 'my.app.plan.monthly',
                'transaction_id' => '30000000000001',
                'purchase_date' => '2025-04-19 06:15:31 Etc/GMT',
                'purchase_date_ms' => '1745043331000',
                'purchase_date_pst' => '2025-04-18 23:15:31 America/Los_Angeles',
                'original_purchase_date' => '2024-03-12 06:15:32 Etc/GMT',
                'original_purchase_date_ms' => '1710224132000',
                'original_purchase_date_pst' => '2024-03-11 23:15:32 America/Los_Angeles',
                'expires_date' => '2025-05-19 06:15:31 Etc/GMT',
                'expires_date_ms' => '1747635331000',
                'expires_date_pst' => '2025-05-18 23:15:31 America/Los_Angeles',
                'web_order_line_item_id' => '30000000000001',
                'is_trial_period' => 'false',
                'is_in_intro_offer_period' => 'false',
                'original_transaction_id' => '10000000000001',
                'in_app_ownership_type' => 'PURCHASED',
                'subscription_group_identifier' => '12345678',
            ],
        ],
        'latest_receipt' => 'dummy_receipt_data==',
        'pending_renewal_info' => [
            [
                'auto_renew_status' => '1',
                'auto_renew_product_id' => 'my.app.plan.monthly',
                'product_id' => 'my.app.plan.monthly',
                'original_transaction_id' => '10000000000001',
            ]
        ]
    ],
    'bid' => 'my.app',
    'bvrs' => '1.0.0',
    'original_transaction_id' => '10000000000001',
    'deprecation' => 'Mon, 1 Jan 2024 00:00:00 GMT',
];

// Your app's shared secret
$sharedSecret = 'dummy_shared_secret';

try {
    $notification = new ServerNotification($data, $sharedSecret);

    echo "Notification Type: " . $notification->getNotificationType()->name . PHP_EOL;
    echo "Environment: " . $notification->getEnvironment()->name . PHP_EOL;
    echo "Product ID: " . $notification->getAutoRenewProductId() . PHP_EOL;
    echo "Auto Renew Status: " . ($notification->getAutoRenewStatus() ? 'true' : 'false') . PHP_EOL;
    echo "Bundle ID: " . $notification->getBundleId() . PHP_EOL;
    echo "App Version: " . $notification->getBvrs() . PHP_EOL;
    echo "Original Transaction ID: " . $notification->getOriginalTransactionId() . PHP_EOL;

    $latest = $notification->getLatestReceipt();
    echo "Receipt Bundle ID: " . $latest->getBundleId() . PHP_EOL;

    $pending = $notification->getPendingRenewalInfo();
    if ($pending) {
        echo "Pending Renewal Product ID: " . $pending->getProductId() . PHP_EOL;
        echo "Auto Renew Status: " . ($pending->getAutoRenewStatus() ? 'active' : 'inactive') . PHP_EOL;
    }

    foreach ($notification->getLatestReceipt()->getLatestReceiptInfo() as $transaction) {
        echo 'getProductId: ' . $transaction->getProductId() . PHP_EOL;
        echo 'getTransactionId: ' . $transaction->getTransactionId() . PHP_EOL;

        if ($transaction->getPurchaseDate() != null) {
            echo 'getPurchaseDate: ' . $transaction->getPurchaseDate()->toIso8601String() . PHP_EOL;
        }
    }

} catch (ValidationException $e) {
    echo "Invalid notification: " . $e->getMessage() . PHP_EOL;
}
