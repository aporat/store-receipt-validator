<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\ServerNotificationSubtype;

/**
 * @group apple-app-store
 */
#[CoversClass(ServerNotificationSubtype::class)]
final class ServerNotificationSubtypeTest extends TestCase
{
    #[DataProvider('allCasesProvider')]
    public function testEnumBackingValue(ServerNotificationSubtype $case, string $expectedValue): void
    {
        self::assertSame($expectedValue, $case->value);
    }

    public static function allCasesProvider(): iterable
    {
        yield 'INITIAL_BUY' => [ServerNotificationSubtype::INITIAL_BUY, 'INITIAL_BUY'];
        yield 'RESUBSCRIBE' => [ServerNotificationSubtype::RESUBSCRIBE, 'RESUBSCRIBE'];
        yield 'DOWNGRADE' => [ServerNotificationSubtype::DOWNGRADE, 'DOWNGRADE'];
        yield 'UPGRADE' => [ServerNotificationSubtype::UPGRADE, 'UPGRADE'];
        yield 'AUTO_RENEW_ENABLED' => [ServerNotificationSubtype::AUTO_RENEW_ENABLED, 'AUTO_RENEW_ENABLED'];
        yield 'AUTO_RENEW_DISABLED' => [ServerNotificationSubtype::AUTO_RENEW_DISABLED, 'AUTO_RENEW_DISABLED'];
        yield 'VOLUNTARY' => [ServerNotificationSubtype::VOLUNTARY, 'VOLUNTARY'];
        yield 'SUMMARY' => [ServerNotificationSubtype::SUMMARY, 'SUMMARY'];
        yield 'BILLING_RETRY' => [ServerNotificationSubtype::BILLING_RETRY, 'BILLING_RETRY'];
        yield 'GRACE_PERIOD' => [ServerNotificationSubtype::GRACE_PERIOD, 'GRACE_PERIOD'];
        yield 'BILLING_RECOVERY' => [ServerNotificationSubtype::BILLING_RECOVERY, 'BILLING_RECOVERY'];
        yield 'PRODUCT_NOT_FOR_SALE' => [ServerNotificationSubtype::PRODUCT_NOT_FOR_SALE, 'PRODUCT_NOT_FOR_SALE'];
        yield 'PENDING' => [ServerNotificationSubtype::PENDING, 'PENDING'];
        yield 'ACCEPTED' => [ServerNotificationSubtype::ACCEPTED, 'ACCEPTED'];
        yield 'PRICE_INCREASE' => [ServerNotificationSubtype::PRICE_INCREASE, 'PRICE_INCREASE'];
        yield 'FAILURE' => [ServerNotificationSubtype::FAILURE, 'FAILURE'];
        yield 'UNREPORTED' => [ServerNotificationSubtype::UNREPORTED, 'UNREPORTED'];
        yield 'UNKNOWN' => [ServerNotificationSubtype::UNKNOWN, 'UNKNOWN'];
    }

    #[DataProvider('fromStringProvider')]
    public function testFromString(string $input, ServerNotificationSubtype $expected): void
    {
        self::assertSame($expected, ServerNotificationSubtype::fromString($input));
    }

    public static function fromStringProvider(): iterable
    {
        yield 'known value' => ['INITIAL_BUY', ServerNotificationSubtype::INITIAL_BUY];
        yield 'another known value' => ['BILLING_RETRY', ServerNotificationSubtype::BILLING_RETRY];
        yield 'unknown value falls back' => ['SOMETHING_NEW', ServerNotificationSubtype::UNKNOWN];
        yield 'empty string falls back' => ['', ServerNotificationSubtype::UNKNOWN];
        yield 'lowercase not matched' => ['initial_buy', ServerNotificationSubtype::UNKNOWN];
    }

    #[DataProvider('subscriptionChangeProvider')]
    public function testIsSubscriptionChange(ServerNotificationSubtype $case, bool $expected): void
    {
        self::assertSame($expected, $case->isSubscriptionChange());
    }

    public static function subscriptionChangeProvider(): iterable
    {
        yield 'INITIAL_BUY' => [ServerNotificationSubtype::INITIAL_BUY, true];
        yield 'RESUBSCRIBE' => [ServerNotificationSubtype::RESUBSCRIBE, true];
        yield 'DOWNGRADE' => [ServerNotificationSubtype::DOWNGRADE, true];
        yield 'UPGRADE' => [ServerNotificationSubtype::UPGRADE, true];
        yield 'AUTO_RENEW_ENABLED' => [ServerNotificationSubtype::AUTO_RENEW_ENABLED, true];
        yield 'AUTO_RENEW_DISABLED' => [ServerNotificationSubtype::AUTO_RENEW_DISABLED, true];
        yield 'VOLUNTARY' => [ServerNotificationSubtype::VOLUNTARY, true];
        yield 'SUMMARY is not subscription change' => [ServerNotificationSubtype::SUMMARY, false];
        yield 'BILLING_RETRY is not subscription change' => [ServerNotificationSubtype::BILLING_RETRY, false];
        yield 'UNKNOWN is not subscription change' => [ServerNotificationSubtype::UNKNOWN, false];
    }

    #[DataProvider('billingRelatedProvider')]
    public function testIsBillingRelated(ServerNotificationSubtype $case, bool $expected): void
    {
        self::assertSame($expected, $case->isBillingRelated());
    }

    public static function billingRelatedProvider(): iterable
    {
        yield 'BILLING_RETRY' => [ServerNotificationSubtype::BILLING_RETRY, true];
        yield 'GRACE_PERIOD' => [ServerNotificationSubtype::GRACE_PERIOD, true];
        yield 'BILLING_RECOVERY' => [ServerNotificationSubtype::BILLING_RECOVERY, true];
        yield 'PRODUCT_NOT_FOR_SALE' => [ServerNotificationSubtype::PRODUCT_NOT_FOR_SALE, true];
        yield 'INITIAL_BUY is not billing' => [ServerNotificationSubtype::INITIAL_BUY, false];
        yield 'PENDING is not billing' => [ServerNotificationSubtype::PENDING, false];
        yield 'UNKNOWN is not billing' => [ServerNotificationSubtype::UNKNOWN, false];
    }

    #[DataProvider('priceChangeProvider')]
    public function testIsPriceChange(ServerNotificationSubtype $case, bool $expected): void
    {
        self::assertSame($expected, $case->isPriceChange());
    }

    public static function priceChangeProvider(): iterable
    {
        yield 'PENDING' => [ServerNotificationSubtype::PENDING, true];
        yield 'ACCEPTED' => [ServerNotificationSubtype::ACCEPTED, true];
        yield 'PRICE_INCREASE' => [ServerNotificationSubtype::PRICE_INCREASE, true];
        yield 'UPGRADE is not price change' => [ServerNotificationSubtype::UPGRADE, false];
        yield 'BILLING_RETRY is not price change' => [ServerNotificationSubtype::BILLING_RETRY, false];
        yield 'UNKNOWN is not price change' => [ServerNotificationSubtype::UNKNOWN, false];
    }

    #[DataProvider('refundReversalProvider')]
    public function testIsRefundReversal(ServerNotificationSubtype $case, bool $expected): void
    {
        self::assertSame($expected, $case->isRefundReversal());
    }

    public static function refundReversalProvider(): iterable
    {
        yield 'FAILURE' => [ServerNotificationSubtype::FAILURE, true];
        yield 'UNREPORTED' => [ServerNotificationSubtype::UNREPORTED, true];
        yield 'INITIAL_BUY is not refund reversal' => [ServerNotificationSubtype::INITIAL_BUY, false];
        yield 'BILLING_RETRY is not refund reversal' => [ServerNotificationSubtype::BILLING_RETRY, false];
        yield 'UNKNOWN is not refund reversal' => [ServerNotificationSubtype::UNKNOWN, false];
    }

    public function testEachCaseBelongsToAtMostOneCategory(): void
    {
        foreach (ServerNotificationSubtype::cases() as $case) {
            $categories = array_filter([
                $case->isSubscriptionChange(),
                $case->isBillingRelated(),
                $case->isPriceChange(),
                $case->isRefundReversal(),
            ]);

            self::assertLessThanOrEqual(
                1,
                count($categories),
                sprintf('%s belongs to multiple categories', $case->name),
            );
        }
    }
}
