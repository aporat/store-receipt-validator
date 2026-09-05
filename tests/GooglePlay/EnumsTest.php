<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\AcknowledgementState;
use ReceiptValidator\GooglePlay\APIError;
use ReceiptValidator\GooglePlay\OneTimeProductNotificationType;
use ReceiptValidator\GooglePlay\RefundType;
use ReceiptValidator\GooglePlay\RevocationContext;
use ReceiptValidator\GooglePlay\SubscriptionNotificationType;
use ReceiptValidator\GooglePlay\SubscriptionState;
use ReceiptValidator\GooglePlay\VoidedProductType;

#[CoversClass(APIError::class)]
#[CoversClass(SubscriptionState::class)]
#[CoversClass(AcknowledgementState::class)]
#[CoversClass(SubscriptionNotificationType::class)]
#[CoversClass(OneTimeProductNotificationType::class)]
#[CoversClass(VoidedProductType::class)]
#[CoversClass(RefundType::class)]
#[CoversClass(RevocationContext::class)]
final class EnumsTest extends TestCase
{
    public function testApiErrorMessagesAndRetryability(): void
    {
        foreach (APIError::cases() as $case) {
            self::assertNotSame('', $case->message());
        }

        self::assertTrue(APIError::BACKEND_ERROR->isRetryable());
        self::assertTrue(APIError::RATE_LIMIT_EXCEEDED->isRetryable());
        self::assertFalse(APIError::NOT_FOUND->isRetryable());
        self::assertFalse(APIError::PURCHASE_TOKEN_NO_LONGER_VALID->isRetryable());

        self::assertSame(APIError::INVALID, APIError::fromString('invalid'));
        self::assertNull(APIError::fromString('somethingNew'));
    }

    public function testSubscriptionStateEntitlement(): void
    {
        self::assertTrue(SubscriptionState::ACTIVE->isEntitled());
        self::assertTrue(SubscriptionState::CANCELED->isEntitled());
        self::assertTrue(SubscriptionState::IN_GRACE_PERIOD->isEntitled());
        self::assertFalse(SubscriptionState::ON_HOLD->isEntitled());
        self::assertFalse(SubscriptionState::PAUSED->isEntitled());
        self::assertFalse(SubscriptionState::EXPIRED->isEntitled());
        self::assertFalse(SubscriptionState::PENDING->isEntitled());
        self::assertFalse(SubscriptionState::UNSPECIFIED->isEntitled());

        self::assertSame(SubscriptionState::ACTIVE, SubscriptionState::fromString('SUBSCRIPTION_STATE_ACTIVE'));
        self::assertSame(SubscriptionState::UNSPECIFIED, SubscriptionState::fromString('nope'));
    }

    public function testAcknowledgementState(): void
    {
        self::assertSame(AcknowledgementState::ACKNOWLEDGED, AcknowledgementState::fromString('ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED'));
        self::assertSame(AcknowledgementState::UNSPECIFIED, AcknowledgementState::fromString('nope'));
        self::assertSame(AcknowledgementState::PENDING, AcknowledgementState::fromProductState(0));
        self::assertSame(AcknowledgementState::ACKNOWLEDGED, AcknowledgementState::fromProductState(1));
        self::assertSame(AcknowledgementState::UNSPECIFIED, AcknowledgementState::fromProductState(null));
        self::assertSame(AcknowledgementState::UNSPECIFIED, AcknowledgementState::fromProductState(9));
    }

    public function testNotificationTypeEnums(): void
    {
        self::assertSame(SubscriptionNotificationType::REVOKED, SubscriptionNotificationType::fromInt(12));
        self::assertSame(SubscriptionNotificationType::PRICE_CHANGE_UPDATED, SubscriptionNotificationType::fromInt(19));
        self::assertSame(SubscriptionNotificationType::UNKNOWN, SubscriptionNotificationType::fromInt(999));
        self::assertTrue(SubscriptionNotificationType::REVOKED->revokesEntitlement());
        self::assertFalse(SubscriptionNotificationType::CANCELED->revokesEntitlement());
        self::assertFalse(SubscriptionNotificationType::EXPIRED->revokesEntitlement());

        self::assertSame(OneTimeProductNotificationType::CANCELED, OneTimeProductNotificationType::fromInt(2));
        self::assertSame(OneTimeProductNotificationType::UNKNOWN, OneTimeProductNotificationType::fromInt(3));

        self::assertSame(VoidedProductType::ONE_TIME, VoidedProductType::fromInt(2));
        self::assertSame(VoidedProductType::UNKNOWN, VoidedProductType::fromInt(3));

        self::assertSame(RefundType::QUANTITY_BASED_PARTIAL, RefundType::fromInt(2));
        self::assertSame(RefundType::UNKNOWN, RefundType::fromInt(3));
    }

    public function testRevocationContextBodies(): void
    {
        self::assertSame('{"revocationContext":{"fullRefund":{}}}', json_encode(RevocationContext::fullRefund()->toArray()));
        self::assertSame('{"revocationContext":{"proratedRefund":{}}}', json_encode(RevocationContext::proratedRefund()->toArray()));
        self::assertSame(
            '{"revocationContext":{"itemBasedRefund":{"productId":"app.example.addon"}}}',
            json_encode(RevocationContext::itemBasedRefund('app.example.addon')->toArray())
        );
    }
}
