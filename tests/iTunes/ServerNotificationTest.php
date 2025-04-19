<?php

namespace ReceiptValidator\Tests\iTunes;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\iTunes\Response;
use ReceiptValidator\iTunes\ServerNotification;
use ReceiptValidator\iTunes\ServerNotificationType;

class ServerNotificationTest extends TestCase
{
    private array $validData;
    private string $sharedSecret = 'dummy_shared_secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->validData = json_decode(file_get_contents(__DIR__ . '/fixtures/serverNotificationRequest.json'), true);
    }

    public function testValidNotificationParsesCorrectly(): void
    {
        $notification = new ServerNotification($this->validData, $this->sharedSecret);

        $this->assertEquals(ServerNotificationType::DID_RENEW, $notification->getNotificationType());
        $this->assertEquals(Environment::PRODUCTION, $notification->getEnvironment());
        $this->assertEquals('my.app.subscription.monthly', $notification->getAutoRenewProductId());
        $this->assertTrue($notification->getAutoRenewStatus());
        $this->assertEquals('my.app', $notification->getBundleId());
        $this->assertEquals('1.0.0', $notification->getBvrs());
        $this->assertEquals('100000000000000', $notification->getOriginalTransactionId());
        $this->assertEquals('dummy_shared_secret', $notification->getPassword());
        $this->assertInstanceOf(Response::class, $notification->getLatestReceipt());
        $this->assertNotNull($notification->getPendingRenewalInfo());
    }

    public function testThrowsOnInvalidSharedSecret(): void
    {
        $this->expectException(ValidationException::class);
        new ServerNotification($this->validData, 'wrong_secret');
    }

    public function testHandlesNullSharedSecret(): void
    {
        $this->expectException(ValidationException::class);
        new ServerNotification($this->validData, null);
    }

    public function testHandlesMissingRenewalInfo(): void
    {
        unset($this->validData['unified_receipt']['pending_renewal_info']);
        $notification = new ServerNotification($this->validData, $this->sharedSecret);
        $this->assertNull($notification->getPendingRenewalInfo());
    }

    public function testHandlesMissingAutoRenewStatusChangeDate(): void
    {
        unset($this->validData['auto_renew_status_change_date_ms']);
        $notification = new ServerNotification($this->validData, $this->sharedSecret);
        $this->assertNull($notification->getAutoRenewStatusChangeDate());
    }

    public function testHandlesSandboxEnvironment(): void
    {
        $this->validData['environment'] = 'SANDBOX';
        $notification = new ServerNotification($this->validData, $this->sharedSecret);
        $this->assertEquals(Environment::SANDBOX, $notification->getEnvironment());
    }
}
