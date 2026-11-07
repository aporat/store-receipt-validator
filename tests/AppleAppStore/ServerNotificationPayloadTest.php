<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\JWT\TokenVerifier;
use ReceiptValidator\AppleAppStore\RenewalInfo;
use ReceiptValidator\AppleAppStore\ServerNotification;
use ReceiptValidator\AppleAppStore\ServerNotificationSubtype;
use ReceiptValidator\AppleAppStore\ServerNotificationType;
use ReceiptValidator\AppleAppStore\Transaction;
use ReceiptValidator\Environment;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\Tests\AppleAppStore\Support\JwsSigning;

/**
 * Notification payloads signed with the test chain, read through a verifier
 * that trusts it: the nested signed blobs and the identity fallbacks.
 *
 * @group apple-app-store
 */
#[CoversClass(ServerNotification::class)]
final class ServerNotificationPayloadTest extends TestCase
{
    use JwsSigning;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function claims(array $data = [], ?string $type = 'DID_RENEW'): array
    {
        $claims = [
            'notificationUUID' => 'f6e8c7b2-1a2b-4c3d-8e9f-0a1b2c3d4e5f',
            'signedDate'       => time() * 1000,
            'version'          => '2.0',
        ];

        if ($type !== null) {
            $claims['notificationType'] = $type;
        }

        if ($data !== []) {
            $claims['data'] = $data;
        }

        return $claims;
    }

    public function testNestedSignedBlobsAreDecodedIntoTypedObjects(): void
    {
        $jws = self::signWithStoreKitChain(self::claims([
            'bundleId'              => 'com.example',
            'environment'           => 'Sandbox',
            'appAppleId'            => '1234567890',
            'signedTransactionInfo' => self::signWithStoreKitChain(self::storeKitTransactionClaims()),
            'signedRenewalInfo'     => self::signWithStoreKitChain(self::storeKitRenewalClaims()),
        ]) + ['subtype' => 'BILLING_RECOVERY']);

        $notification = new ServerNotification(['signedPayload' => $jws], self::storeKitVerifier());

        self::assertSame(ServerNotificationType::DID_RENEW, $notification->getNotificationType());
        self::assertSame(ServerNotificationSubtype::BILLING_RECOVERY, $notification->getSubtype());
        self::assertSame('com.example', $notification->getBundleId());
        self::assertSame(1234567890, $notification->getAppAppleId());
        self::assertSame(Environment::SANDBOX, $notification->getEnvironment());
        self::assertSame('f6e8c7b2-1a2b-4c3d-8e9f-0a1b2c3d4e5f', $notification->getNotificationUUID());

        $transaction = $notification->getTransaction();
        self::assertInstanceOf(Transaction::class, $transaction);
        self::assertSame('2000000123456789', $transaction->getTransactionId());

        $renewal = $notification->getRenewalInfo();
        self::assertInstanceOf(RenewalInfo::class, $renewal);
        self::assertSame('2000000000000001', $renewal->getOriginalTransactionId());
    }

    public function testNotificationWithoutNestedBlobsHasNoTransactionOrRenewalInfo(): void
    {
        $jws = self::signWithStoreKitChain(self::claims([
            'bundleId'    => 'com.example',
            'environment' => 'Production',
        ]));

        $notification = new ServerNotification(['signedPayload' => $jws], self::storeKitVerifier());

        self::assertNull($notification->getTransaction());
        self::assertNull($notification->getRenewalInfo());
        self::assertNull($notification->getAppAppleId());
        self::assertNull($notification->getSubtype());
        self::assertSame(Environment::PRODUCTION, $notification->getEnvironment());
    }

    public function testNotificationWithoutAnyIdentitySectionIsRejected(): void
    {
        $jws = self::signWithStoreKitChain(self::claims());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('missing its environment');
        new ServerNotification(['signedPayload' => $jws], self::storeKitVerifier());
    }

    public function testNotificationSignedByAnUntrustedChainIsRejectedByDefault(): void
    {
        $jws = self::signWithStoreKitChain(self::claims(['bundleId' => 'com.example', 'environment' => 'Sandbox']));

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not rooted to a trusted Apple certificate');
        new ServerNotification(['signedPayload' => $jws], new TokenVerifier());
    }

    public function testEmptyNotificationTypeIsRejected(): void
    {
        $jws = self::signWithStoreKitChain(
            self::claims(['bundleId' => 'com.example', 'environment' => 'Sandbox'], type: '')
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('missing its notificationType');
        new ServerNotification(['signedPayload' => $jws], self::storeKitVerifier());
    }
}
