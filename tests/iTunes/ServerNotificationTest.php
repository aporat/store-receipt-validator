<?php

namespace ReceiptValidator\Tests\iTunes;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\ServerNotification;
use ReceiptValidator\iTunes\ServerNotificationType;

class ServerNotificationTest extends TestCase
{
    public function testValidNotificationParsesCorrectly(): void
    {
        $data = json_decode(file_get_contents(__DIR__ . '/fixtures/serverNotificationRequest.json'), true);
        $sharedSecret = 'dummy_shared_secret';

        $notification = new ServerNotification($data, $sharedSecret);

        $this->assertEquals(ServerNotificationType::DID_RENEW, $notification->getNotificationType());
        $this->assertEquals(Environment::PRODUCTION, $notification->getEnvironment());
        $this->assertEquals('my.app.subscription.monthly', $notification->getAutoRenewProductId());
        $this->assertTrue($notification->getAutoRenewStatus());
        $this->assertEquals('my.app', $notification->getBundleId());
        $this->assertEquals('1.0.0', $notification->getBvrs());
        $this->assertEquals('100000000000000', $notification->getOriginalTransactionId());
        $this->assertNotNull($notification->getLatestReceipt());
        $this->assertNotNull($notification->getPendingRenewalInfo());
    }

    public function testThrowsOnInvalidSharedSecret(): void
    {
        $this->expectException(ValidationException::class);

        $data = json_decode(file_get_contents(__DIR__ . '/fixtures/serverNotificationRequest.json'), true);
        new ServerNotification($data, 'wrong_secret');
    }
}
