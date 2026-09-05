<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\OneTimeProductNotification;
use ReceiptValidator\GooglePlay\OneTimeProductNotificationType;
use ReceiptValidator\GooglePlay\RefundType;
use ReceiptValidator\GooglePlay\ServerNotification;
use ReceiptValidator\GooglePlay\SubscriptionNotification;
use ReceiptValidator\GooglePlay\SubscriptionNotificationType;
use ReceiptValidator\GooglePlay\VoidedProductType;
use ReceiptValidator\GooglePlay\VoidedPurchaseNotification;

#[CoversClass(ServerNotification::class)]
#[CoversClass(SubscriptionNotification::class)]
#[CoversClass(OneTimeProductNotification::class)]
#[CoversClass(VoidedPurchaseNotification::class)]
final class ServerNotificationTest extends TestCase
{
    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(__DIR__ . "/fixtures/{$name}.json"), true);
    }

    /**
     * @param array<string, mixed> $notification
     * @return array<string, mixed>
     */
    private function envelope(array $notification): array
    {
        return [
            'message' => [
                'data'        => base64_encode((string) json_encode($notification)),
                'messageId'   => '1234567890',
                'publishTime' => '2026-09-03T15:00:00.000Z',
            ],
            'subscription' => 'projects/test-project/subscriptions/rtdn-push',
        ];
    }

    public function testParsesSubscriptionNotification(): void
    {
        $data = $this->fixture('rtdnSubscription');
        $n    = new ServerNotification($data);

        self::assertSame('1.0', $n->getVersion());
        self::assertSame('app.example', $n->getPackageName());
        self::assertSame(1756911600, $n->getEventTime()->getTimestamp());
        self::assertSame($data, $n->getRawData());
        self::assertTrue($n->isSubscriptionNotification());
        self::assertFalse($n->isOneTimeProductNotification());
        self::assertFalse($n->isVoidedPurchaseNotification());
        self::assertFalse($n->isTestNotification());
        self::assertSame('sub-token-123', $n->getPurchaseToken());
        self::assertNull($n->getOneTimeProductNotification());
        self::assertNull($n->getVoidedPurchaseNotification());

        $sub = $n->getSubscriptionNotification();
        self::assertInstanceOf(SubscriptionNotification::class, $sub);
        self::assertSame('1.0', $sub->getVersion());
        self::assertSame(SubscriptionNotificationType::RENEWED, $sub->getNotificationType());
        self::assertSame(2, $sub->getRawNotificationType());
        self::assertSame('sub-token-123', $sub->getPurchaseToken());
        self::assertSame('app.example.subscription', $sub->getSubscriptionId());
    }

    public function testParsesOneTimeProductNotification(): void
    {
        $n = new ServerNotification($this->fixture('rtdnOneTime'));

        self::assertTrue($n->isOneTimeProductNotification());
        self::assertSame('one-time-token-123', $n->getPurchaseToken());

        $otp = $n->getOneTimeProductNotification();
        self::assertInstanceOf(OneTimeProductNotification::class, $otp);
        self::assertSame('1.0', $otp->getVersion());
        self::assertSame(OneTimeProductNotificationType::PURCHASED, $otp->getNotificationType());
        self::assertSame(1, $otp->getRawNotificationType());
        self::assertSame('one-time-token-123', $otp->getPurchaseToken());
        self::assertSame('app.example.coins.100', $otp->getSku());
    }

    public function testParsesVoidedPurchaseNotification(): void
    {
        $n = new ServerNotification($this->fixture('rtdnVoided'));

        self::assertTrue($n->isVoidedPurchaseNotification());
        self::assertSame('voided-token-123', $n->getPurchaseToken());

        $voided = $n->getVoidedPurchaseNotification();
        self::assertInstanceOf(VoidedPurchaseNotification::class, $voided);
        self::assertSame('voided-token-123', $voided->getPurchaseToken());
        self::assertSame('GPA.1000-2000-3000-40000', $voided->getOrderId());
        self::assertSame(VoidedProductType::SUBSCRIPTION, $voided->getProductType());
        self::assertSame(RefundType::FULL, $voided->getRefundType());
    }

    public function testParsesTestNotification(): void
    {
        $n = new ServerNotification($this->fixture('rtdnTest'));

        self::assertTrue($n->isTestNotification());
        self::assertFalse($n->isSubscriptionNotification());
        self::assertNull($n->getPurchaseToken());
        self::assertNull($n->getSubscriptionNotification());
    }

    public function testUnknownTypesFallBackGracefully(): void
    {
        $n = new ServerNotification([
            'packageName'              => 'app.example',
            'subscriptionNotification' => ['notificationType' => 99, 'purchaseToken' => ''],
            'voidedPurchaseNotification' => ['productType' => 7, 'refundType' => 7],
        ]);

        self::assertNull($n->getVersion());
        self::assertSame(0, $n->getEventTime()->getTimestamp());
        self::assertSame(SubscriptionNotificationType::UNKNOWN, $n->getSubscriptionNotification()?->getNotificationType());
        self::assertSame(99, $n->getSubscriptionNotification()?->getRawNotificationType());
        self::assertNull($n->getSubscriptionNotification()?->getSubscriptionId());
        self::assertSame(VoidedProductType::UNKNOWN, $n->getVoidedPurchaseNotification()?->getProductType());
        self::assertSame(RefundType::UNKNOWN, $n->getVoidedPurchaseNotification()?->getRefundType());
        // Empty subscription token is skipped; empty voided token yields null overall.
        self::assertNull($n->getPurchaseToken());

        $otp = new OneTimeProductNotification(['notificationType' => 5]);
        self::assertSame(OneTimeProductNotificationType::UNKNOWN, $otp->getNotificationType());
        self::assertSame('', $otp->getPurchaseToken());
        self::assertNull($otp->getSku());
    }

    public function testRequiresPackageName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('packageName is missing');

        new ServerNotification(['testNotification' => []]);
    }

    public function testRequiresARecognisedPayload(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('does not contain a recognised payload');

        new ServerNotification(['packageName' => 'app.example', 'testNotification' => null]);
    }

    public function testFromPubSubMessageUnwrapsEnvelope(): void
    {
        $data = $this->fixture('rtdnSubscription');
        $n    = ServerNotification::fromPubSubMessage($this->envelope($data));

        self::assertSame($data, $n->getRawData());
        self::assertSame('sub-token-123', $n->getPurchaseToken());
    }

    public function testFromPubSubMessageRejectsMissingData(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('missing message.data');

        ServerNotification::fromPubSubMessage(['message' => ['messageId' => '1']]);
    }

    public function testFromPubSubMessageRejectsMissingMessage(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('missing message.data');

        ServerNotification::fromPubSubMessage([]);
    }

    public function testFromPubSubMessageRejectsBadBase64(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not valid base64');

        ServerNotification::fromPubSubMessage(['message' => ['data' => '!!!not base64!!!']]);
    }

    public function testFromPubSubMessageRejectsBadJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('not valid JSON');

        ServerNotification::fromPubSubMessage(['message' => ['data' => base64_encode('{oops')]]);
    }

    public function testFromPubSubMessageRejectsNonObjectJson(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('did not decode to a JSON object');

        ServerNotification::fromPubSubMessage(['message' => ['data' => base64_encode('"string"')]]);
    }
}
