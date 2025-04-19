<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\ServerNotification;
use ReceiptValidator\AppleAppStore\ServerNotificationType;
use ReceiptValidator\AppleAppStore\ServerNotificationSubtype;
use ReceiptValidator\AppleAppStore\RenewalInfo;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Environment;

class ServerNotificationTest extends TestCase
{
    public function testNotificationParsesCorrectly(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/test-notification-signed-payload.json');
        $data = json_decode($json, true);
        $notification = new ServerNotification($data['signedPayload']);

        $this->assertInstanceOf(ServerNotification::class, $notification);
        $this->assertInstanceOf(ServerNotificationType::class, $notification->getNotificationType());
        $this->assertNotEmpty($notification->getNotificationUUID());
        $this->assertInstanceOf(Environment::class, $notification->getEnvironment());
        $this->assertNotEmpty($notification->getBundleId());
        $this->assertInstanceOf(\Carbon\Carbon::class, $notification->getSignedDate());

        if ($notification->getSubtype()) {
            $this->assertInstanceOf(ServerNotificationSubtype::class, $notification->getSubtype());
        }

        if ($notification->getTransaction()) {
            $this->assertInstanceOf(Transaction::class, $notification->getTransaction());
        }

        if ($notification->getRenewalInfo()) {
            $this->assertInstanceOf(RenewalInfo::class, $notification->getRenewalInfo());
        }
    }
}
