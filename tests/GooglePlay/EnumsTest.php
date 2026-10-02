<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\AcknowledgementState;
use ReceiptValidator\GooglePlay\CancelSurveyReason;
use ReceiptValidator\GooglePlay\ConsentState;
use ReceiptValidator\GooglePlay\ConsumptionState;
use ReceiptValidator\GooglePlay\OrderRefundReason;
use ReceiptValidator\GooglePlay\OrderState;
use ReceiptValidator\GooglePlay\PriceChangeMode;
use ReceiptValidator\GooglePlay\PriceChangeState;
use ReceiptValidator\GooglePlay\ProductPurchaseState;
use ReceiptValidator\GooglePlay\APIError;
use ReceiptValidator\GooglePlay\APIException;
use ReceiptValidator\GooglePlay\OneTimeProductNotificationType;
use ReceiptValidator\GooglePlay\RefundPreference;
use ReceiptValidator\GooglePlay\RefundType;
use ReceiptValidator\GooglePlay\ReplacementMode;
use ReceiptValidator\GooglePlay\ReviewRefundRequest;
use ReceiptValidator\GooglePlay\SalesChannel;
use ReceiptValidator\GooglePlay\RevocationContext;
use ReceiptValidator\GooglePlay\SubscriptionCancellationType;
use ReceiptValidator\GooglePlay\SubscriptionNotificationType;
use ReceiptValidator\GooglePlay\SubscriptionOfferPhase;
use ReceiptValidator\GooglePlay\SubscriptionState;
use ReceiptValidator\GooglePlay\VoidedProductType;

#[CoversClass(APIError::class)]
#[CoversClass(APIException::class)]
#[CoversClass(CancelSurveyReason::class)]
#[CoversClass(ConsentState::class)]
#[CoversClass(OrderRefundReason::class)]
#[CoversClass(OrderState::class)]
#[CoversClass(PriceChangeMode::class)]
#[CoversClass(PriceChangeState::class)]
#[CoversClass(RefundPreference::class)]
#[CoversClass(ReplacementMode::class)]
#[CoversClass(ReviewRefundRequest::class)]
#[CoversClass(SalesChannel::class)]
#[CoversClass(SubscriptionOfferPhase::class)]
#[CoversClass(SubscriptionState::class)]
#[CoversClass(AcknowledgementState::class)]
#[CoversClass(ConsumptionState::class)]
#[CoversClass(ProductPurchaseState::class)]
#[CoversClass(SubscriptionCancellationType::class)]
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
        self::assertSame(SubscriptionNotificationType::ITEMS_CHANGED, SubscriptionNotificationType::fromInt(17));
        self::assertSame(SubscriptionNotificationType::CANCELLATION_SCHEDULED, SubscriptionNotificationType::fromInt(18));
        self::assertSame(SubscriptionNotificationType::PRICE_STEP_UP_CONSENT_UPDATED, SubscriptionNotificationType::fromInt(22));
        self::assertFalse(SubscriptionNotificationType::CANCELLATION_SCHEDULED->revokesEntitlement());
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

    public function testV2StringMappings(): void
    {
        self::assertSame(ProductPurchaseState::PURCHASED, ProductPurchaseState::fromV2String('PURCHASED'));
        self::assertSame(ProductPurchaseState::CANCELED, ProductPurchaseState::fromV2String('CANCELLED'));
        self::assertSame(ProductPurchaseState::PENDING, ProductPurchaseState::fromV2String('PENDING'));
        self::assertNull(ProductPurchaseState::fromV2String('PURCHASE_STATE_UNSPECIFIED'));
        self::assertNull(ProductPurchaseState::fromV2String(null));

        self::assertSame(ConsumptionState::YET_TO_BE_CONSUMED, ConsumptionState::fromV2String('CONSUMPTION_STATE_YET_TO_BE_CONSUMED'));
        self::assertSame(ConsumptionState::CONSUMED, ConsumptionState::fromV2String('CONSUMPTION_STATE_CONSUMED'));
        self::assertNull(ConsumptionState::fromV2String('CONSUMPTION_STATE_UNSPECIFIED'));
        self::assertNull(ConsumptionState::fromV2String(null));

        self::assertSame('USER_REQUESTED_STOP_RENEWALS', SubscriptionCancellationType::USER_REQUESTED_STOP_RENEWALS->value);
        self::assertSame('DEVELOPER_REQUESTED_STOP_PAYMENTS', SubscriptionCancellationType::DEVELOPER_REQUESTED_STOP_PAYMENTS->value);
    }

    public function testStringEnumsFallBackToUnspecified(): void
    {
        self::assertSame(CancelSurveyReason::COST_RELATED, CancelSurveyReason::fromString('CANCEL_SURVEY_REASON_COST_RELATED'));
        self::assertSame(CancelSurveyReason::UNSPECIFIED, CancelSurveyReason::fromString('nope'));
        self::assertSame(CancelSurveyReason::UNSPECIFIED, CancelSurveyReason::fromString(null));
        self::assertSame(ConsentState::COMPLETED, ConsentState::fromString('COMPLETED'));
        self::assertSame(ConsentState::UNSPECIFIED, ConsentState::fromString('x'));
        self::assertSame(OrderRefundReason::OTHER, OrderRefundReason::fromString('OTHER'));
        self::assertSame(OrderState::PARTIALLY_REFUNDED, OrderState::fromString('PARTIALLY_REFUNDED'));
        self::assertSame(OrderState::UNSPECIFIED, OrderState::fromString('x'));
        self::assertSame(PriceChangeMode::OPT_OUT_PRICE_INCREASE, PriceChangeMode::fromString('OPT_OUT_PRICE_INCREASE'));
        self::assertSame(PriceChangeState::APPLIED, PriceChangeState::fromString('APPLIED'));
        self::assertSame(ReplacementMode::KEEP_EXISTING, ReplacementMode::fromString('KEEP_EXISTING'));
        self::assertSame(SalesChannel::OUTSIDE_PLAY_STORE, SalesChannel::fromString('OUTSIDE_PLAY_STORE'));
        self::assertSame(SubscriptionOfferPhase::BASE_PRICE, SubscriptionOfferPhase::fromString('BASE_PRICE'));
        self::assertSame(SubscriptionOfferPhase::UNSPECIFIED, SubscriptionOfferPhase::fromString(null));
    }

    public function testApiExceptionExposesStructuredError(): void
    {
        $known = new APIException('msg', 429, 'rateLimitExceeded', APIError::RATE_LIMIT_EXCEEDED);
        self::assertSame(429, $known->getStatusCode());
        self::assertSame(429, $known->getCode());
        self::assertSame('rateLimitExceeded', $known->getReason());
        self::assertSame(APIError::RATE_LIMIT_EXCEEDED, $known->getError());
        self::assertTrue($known->isRetryable());

        $knownFatal = new APIException('msg', 400, 'invalid', APIError::INVALID);
        self::assertFalse($knownFatal->isRetryable());

        $unknown429 = new APIException('msg', 429, 'somethingNew');
        self::assertNull($unknown429->getError());
        self::assertSame('somethingNew', $unknown429->getReason());
        self::assertTrue($unknown429->isRetryable());

        self::assertTrue((new APIException('msg', 503))->isRetryable());
        self::assertFalse((new APIException('msg', 404))->isRetryable());
        self::assertFalse((new APIException('msg', 400, 'somethingNew'))->isRetryable());
    }

    public function testReviewRefundRequestBodies(): void
    {
        $minimal = new ReviewRefundRequest('prt-1');
        self::assertSame(
            '{"pendingRefundToken":"prt-1","refundPreference":"NEUTRAL"}',
            json_encode($minimal->toArray())
        );

        $full = (new ReviewRefundRequest(
            'prt-2',
            RefundPreference::DECLINE,
            sampleContentProvided: true,
            consumptionUsageEvents: [['obfuscatedAccountId' => 'acc', 'consumptionTime' => '2026-09-01T00:00:00Z']],
        ))->withConsumptionPercent(87.5);

        self::assertSame(87500, $full->consumptionPercentageMilliunits);
        self::assertSame(
            '{"pendingRefundToken":"prt-2","refundPreference":"DECLINE","sampleContentProvided":true,'
            . '"consumptionPercentageMilliunits":87500,'
            . '"consumptionUsageEvents":[{"obfuscatedAccountId":"acc","consumptionTime":"2026-09-01T00:00:00Z"}]}',
            json_encode($full->toArray())
        );

        self::assertSame(100000, (new ReviewRefundRequest('p'))->withConsumptionPercent(250)->consumptionPercentageMilliunits);
        self::assertSame(0, (new ReviewRefundRequest('p'))->withConsumptionPercent(-5)->consumptionPercentageMilliunits);
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
