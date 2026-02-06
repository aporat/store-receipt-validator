<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\ServerNotificationType;

/**
 * @group apple-app-store
 */
#[CoversClass(ServerNotificationType::class)]
final class ServerNotificationTypeTest extends TestCase
{
    #[DataProvider('allCasesProvider')]
    public function testEnumBackingValue(ServerNotificationType $case, string $expectedValue): void
    {
        self::assertSame($expectedValue, $case->value);
    }

    public static function allCasesProvider(): iterable
    {
        yield 'SUBSCRIBED' => [ServerNotificationType::SUBSCRIBED, 'SUBSCRIBED'];
        yield 'DID_RENEW' => [ServerNotificationType::DID_RENEW, 'DID_RENEW'];
        yield 'DID_CHANGE_RENEWAL_PREF' => [ServerNotificationType::DID_CHANGE_RENEWAL_PREF, 'DID_CHANGE_RENEWAL_PREF'];
        yield 'DID_CHANGE_RENEWAL_STATUS' => [ServerNotificationType::DID_CHANGE_RENEWAL_STATUS, 'DID_CHANGE_RENEWAL_STATUS'];
        yield 'EXPIRED' => [ServerNotificationType::EXPIRED, 'EXPIRED'];
        yield 'DID_FAIL_TO_RENEW' => [ServerNotificationType::DID_FAIL_TO_RENEW, 'DID_FAIL_TO_RENEW'];
        yield 'GRACE_PERIOD_EXPIRED' => [ServerNotificationType::GRACE_PERIOD_EXPIRED, 'GRACE_PERIOD_EXPIRED'];
        yield 'RENEWAL_EXTENDED' => [ServerNotificationType::RENEWAL_EXTENDED, 'RENEWAL_EXTENDED'];
        yield 'RENEWAL_EXTENSION' => [ServerNotificationType::RENEWAL_EXTENSION, 'RENEWAL_EXTENSION'];
        yield 'OFFER_REDEEMED' => [ServerNotificationType::OFFER_REDEEMED, 'OFFER_REDEEMED'];
        yield 'PRICE_INCREASE' => [ServerNotificationType::PRICE_INCREASE, 'PRICE_INCREASE'];
        yield 'REFUND' => [ServerNotificationType::REFUND, 'REFUND'];
        yield 'REFUND_DECLINED' => [ServerNotificationType::REFUND_DECLINED, 'REFUND_DECLINED'];
        yield 'REFUND_REVERSED' => [ServerNotificationType::REFUND_REVERSED, 'REFUND_REVERSED'];
        yield 'REVOKE' => [ServerNotificationType::REVOKE, 'REVOKE'];
        yield 'CONSUMPTION_REQUEST' => [ServerNotificationType::CONSUMPTION_REQUEST, 'CONSUMPTION_REQUEST'];
        yield 'ONE_TIME_CHARGE' => [ServerNotificationType::ONE_TIME_CHARGE, 'ONE_TIME_CHARGE'];
        yield 'EXTERNAL_PURCHASE_TOKEN' => [ServerNotificationType::EXTERNAL_PURCHASE_TOKEN, 'EXTERNAL_PURCHASE_TOKEN'];
        yield 'TEST' => [ServerNotificationType::TEST, 'TEST'];
        yield 'UNKNOWN' => [ServerNotificationType::UNKNOWN, 'UNKNOWN'];
    }

    #[DataProvider('fromStringProvider')]
    public function testFromString(string $input, ServerNotificationType $expected): void
    {
        self::assertSame($expected, ServerNotificationType::fromString($input));
    }

    public static function fromStringProvider(): iterable
    {
        yield 'known value' => ['SUBSCRIBED', ServerNotificationType::SUBSCRIBED];
        yield 'another known value' => ['REFUND', ServerNotificationType::REFUND];
        yield 'test type' => ['TEST', ServerNotificationType::TEST];
        yield 'unknown value falls back' => ['FUTURE_TYPE', ServerNotificationType::UNKNOWN];
        yield 'empty string falls back' => ['', ServerNotificationType::UNKNOWN];
        yield 'lowercase not matched' => ['subscribed', ServerNotificationType::UNKNOWN];
    }

    #[DataProvider('subscriptionLifecycleProvider')]
    public function testIsSubscriptionLifecycle(ServerNotificationType $case, bool $expected): void
    {
        self::assertSame($expected, $case->isSubscriptionLifecycle());
    }

    public static function subscriptionLifecycleProvider(): iterable
    {
        yield 'SUBSCRIBED' => [ServerNotificationType::SUBSCRIBED, true];
        yield 'DID_RENEW' => [ServerNotificationType::DID_RENEW, true];
        yield 'DID_CHANGE_RENEWAL_PREF' => [ServerNotificationType::DID_CHANGE_RENEWAL_PREF, true];
        yield 'DID_CHANGE_RENEWAL_STATUS' => [ServerNotificationType::DID_CHANGE_RENEWAL_STATUS, true];
        yield 'EXPIRED' => [ServerNotificationType::EXPIRED, true];
        yield 'DID_FAIL_TO_RENEW' => [ServerNotificationType::DID_FAIL_TO_RENEW, true];
        yield 'GRACE_PERIOD_EXPIRED' => [ServerNotificationType::GRACE_PERIOD_EXPIRED, true];
        yield 'RENEWAL_EXTENDED' => [ServerNotificationType::RENEWAL_EXTENDED, true];
        yield 'RENEWAL_EXTENSION' => [ServerNotificationType::RENEWAL_EXTENSION, true];
        yield 'REFUND is not subscription lifecycle' => [ServerNotificationType::REFUND, false];
        yield 'TEST is not subscription lifecycle' => [ServerNotificationType::TEST, false];
        yield 'UNKNOWN is not subscription lifecycle' => [ServerNotificationType::UNKNOWN, false];
    }

    #[DataProvider('refundRelatedProvider')]
    public function testIsRefundRelated(ServerNotificationType $case, bool $expected): void
    {
        self::assertSame($expected, $case->isRefundRelated());
    }

    public static function refundRelatedProvider(): iterable
    {
        yield 'REFUND' => [ServerNotificationType::REFUND, true];
        yield 'REFUND_DECLINED' => [ServerNotificationType::REFUND_DECLINED, true];
        yield 'REFUND_REVERSED' => [ServerNotificationType::REFUND_REVERSED, true];
        yield 'REVOKE' => [ServerNotificationType::REVOKE, true];
        yield 'SUBSCRIBED is not refund related' => [ServerNotificationType::SUBSCRIBED, false];
        yield 'OFFER_REDEEMED is not refund related' => [ServerNotificationType::OFFER_REDEEMED, false];
        yield 'UNKNOWN is not refund related' => [ServerNotificationType::UNKNOWN, false];
    }

    #[DataProvider('offerOrPriceEventProvider')]
    public function testIsOfferOrPriceEvent(ServerNotificationType $case, bool $expected): void
    {
        self::assertSame($expected, $case->isOfferOrPriceEvent());
    }

    public static function offerOrPriceEventProvider(): iterable
    {
        yield 'OFFER_REDEEMED' => [ServerNotificationType::OFFER_REDEEMED, true];
        yield 'PRICE_INCREASE' => [ServerNotificationType::PRICE_INCREASE, true];
        yield 'SUBSCRIBED is not offer/price' => [ServerNotificationType::SUBSCRIBED, false];
        yield 'REFUND is not offer/price' => [ServerNotificationType::REFUND, false];
        yield 'TEST is not offer/price' => [ServerNotificationType::TEST, false];
        yield 'UNKNOWN is not offer/price' => [ServerNotificationType::UNKNOWN, false];
    }

    public function testEachCaseBelongsToAtMostOneCategory(): void
    {
        foreach (ServerNotificationType::cases() as $case) {
            $categories = array_filter([
                $case->isSubscriptionLifecycle(),
                $case->isRefundRelated(),
                $case->isOfferOrPriceEvent(),
            ]);

            self::assertLessThanOrEqual(
                1,
                count($categories),
                sprintf('%s belongs to multiple categories', $case->name),
            );
        }
    }
}
