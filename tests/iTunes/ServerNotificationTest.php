<?php

namespace ReceiptValidator\Tests\iTunes;

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
        $this->validData = json_decode(
            file_get_contents(__DIR__ . '/fixtures/serverNotificationRequest.json'),
            true
        );
        self::assertIsArray($this->validData, 'Fixture could not be decoded to array');
    }

    public function testValidNotificationParsesCorrectly(): void
    {
        $n = new ServerNotification($this->validData, $this->sharedSecret);

        self::assertSame(ServerNotificationType::DID_RENEW, $n->getNotificationType());
        self::assertSame(Environment::PRODUCTION, $n->getEnvironment());
        self::assertSame('my.app.subscription.monthly', $n->getAutoRenewProductId());
        self::assertTrue($n->getAutoRenewStatus());
        self::assertSame('my.app', $n->getBundleId());
        self::assertSame('1.0.0', $n->getBvrs());
        self::assertSame('100000000000000', $n->getOriginalTransactionId());
        self::assertSame('dummy_shared_secret', $n->getPassword());
        self::assertInstanceOf(Response::class, $n->getLatestReceipt());
        self::assertNotNull($n->getPendingRenewalInfo());

        // ms → sec date cast
        $ms = $this->validData['auto_renew_status_change_date_ms'] ?? null;
        if (is_int($ms)) {
            self::assertNotNull($n->getAutoRenewStatusChangeDate());
            self::assertSame(intdiv($ms, 1000), $n->getAutoRenewStatusChangeDate()->getTimestamp());
        }
    }

    public function testThrowsOnInvalidSharedSecret(): void
    {
        $this->expectException(ValidationException::class);
        new ServerNotification($this->validData, 'wrong_secret');
    }

    public function testAllowsNullSharedSecret(): void
    {
        // With the new implementation, null means “don’t validate password”
        $n = new ServerNotification($this->validData, null);

        self::assertSame(ServerNotificationType::DID_RENEW, $n->getNotificationType());
        self::assertSame($this->validData['password'], $n->getPassword());
    }

    public function testHandlesMissingRenewalInfo(): void
    {
        $data = $this->validData;
        unset($data['unified_receipt']['pending_renewal_info']);

        $n = new ServerNotification($data, $this->sharedSecret);
        self::assertNull($n->getPendingRenewalInfo());
    }

    public function testHandlesMissingAutoRenewStatusChangeDate(): void
    {
        $data = $this->validData;
        unset($data['auto_renew_status_change_date_ms']);

        $n = new ServerNotification($data, $this->sharedSecret);
        self::assertNull($n->getAutoRenewStatusChangeDate());
    }

    public function testHandlesSandboxEnvironment(): void
    {
        $data = $this->validData;
        $data['environment'] = 'SANDBOX';

        $n = new ServerNotification($data, $this->sharedSecret);
        self::assertSame(Environment::SANDBOX, $n->getEnvironment());
    }

    public function testThrowsWhenUnifiedReceiptMissing(): void
    {
        $data = $this->validData;
        unset($data['unified_receipt']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing or invalid unified_receipt');

        new ServerNotification($data, $this->sharedSecret);
    }

    public function testThrowsOnUnknownNotificationType(): void
    {
        $data = $this->validData;
        $data['notification_type'] = 'NOT_A_REAL_TYPE';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown notification_type');

        new ServerNotification($data, $this->sharedSecret);
    }
}
