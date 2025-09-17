<?php

namespace ReceiptValidator\Tests\AppleAppStore;

use Carbon\CarbonInterface;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\RenewalInfo;
use ReceiptValidator\AppleAppStore\ServerNotification;
use ReceiptValidator\AppleAppStore\ServerNotificationSubtype;
use ReceiptValidator\AppleAppStore\ServerNotificationType;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;

final class ServerNotificationTest extends TestCase
{
    public function testNotificationParsesCorrectly(): void
    {
        $json = file_get_contents(__DIR__ . '/fixtures/test-notification-signed-payload.json');
        $data = json_decode($json, true);

        $n = new ServerNotification($data);

        // Basic type checks
        self::assertInstanceOf(ServerNotification::class, $n);
        self::assertInstanceOf(ServerNotificationType::class, $n->getNotificationType());

        // UUID & bundle invariants (don’t overfit to exact values)
        self::assertNotSame('', $n->getNotificationUUID());
        self::assertTrue($this->looksLikeUuid($n->getNotificationUUID()), 'notificationUUID should look like a UUID');

        self::assertNotSame('', $n->getBundleId());
        self::assertMatchesRegularExpression('/^[A-Za-z0-9.-]+\.[A-Za-z0-9.-]+$/', $n->getBundleId(), 'bundleId should look like reverse-DNS');

        // Environment & signed date
        self::assertInstanceOf(Environment::class, $n->getEnvironment());
        self::assertContains($n->getEnvironment(), [Environment::PRODUCTION, Environment::SANDBOX]);
        self::assertInstanceOf(CarbonInterface::class, $n->getSignedDate());
        self::assertGreaterThan(0, $n->getSignedDate()->getTimestamp());

        // Optional subtype / objects: if present, they should be parsed and typed
        $sub = $n->getSubtype();
        if ($sub !== null) {
            self::assertInstanceOf(ServerNotificationSubtype::class, $sub);
        }

        $tx = $n->getTransaction();
        if ($tx !== null) {
            self::assertInstanceOf(Transaction::class, $tx);
            // Light sanity checks without coupling to fixture content
            self::assertNotSame('', $tx->getTransactionId() ?? '');
            self::assertNotSame('', $tx->getProductId() ?? '');
        }

        $ri = $n->getRenewalInfo();
        if ($ri !== null) {
            self::assertInstanceOf(RenewalInfo::class, $ri);
            // Light sanity checks
            self::assertNotSame('', $ri->getOriginalTransactionId() ?? '');
        }
    }

    public function testThrowsOnMissingSignedPayload(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('signedPayload key is missing');

        new ServerNotification(['notSignedPayload' => 'oops']);
    }

    /** Simple “looks like a UUID” check without locking to a specific version. */
    private function looksLikeUuid(string $s): bool
    {
        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $s
        );
    }
}
