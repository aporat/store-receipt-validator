<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\Amazon;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\APIError;
use ReceiptValidator\Amazon\CancelReason;
use ReceiptValidator\Amazon\FulfillmentResult;
use ReceiptValidator\Amazon\ProductType;
use ReceiptValidator\Amazon\PromotionStatus;
use ReceiptValidator\Amazon\PromotionType;
use ReceiptValidator\Exceptions\ValidationException;

/**
 * @group amazon
 */
#[CoversClass(APIError::class)]
#[CoversClass(ProductType::class)]
#[CoversClass(CancelReason::class)]
#[CoversClass(FulfillmentResult::class)]
#[CoversClass(PromotionType::class)]
#[CoversClass(PromotionStatus::class)]
final class EnumsTest extends TestCase
{
    #[DataProvider('statusCodeProvider')]
    public function testApiErrorIsKeyedByHttpStatus(APIError $case, int $status): void
    {
        self::assertSame($status, $case->value);
        self::assertSame($case, APIError::from($status));
        self::assertNotSame('', $case->message());
    }

    public static function statusCodeProvider(): iterable
    {
        yield 'INVALID_RECEIPT' => [APIError::INVALID_RECEIPT, 400];
        yield 'RECEIPT_NO_LONGER_VALID' => [APIError::RECEIPT_NO_LONGER_VALID, 410];
        yield 'THROTTLED' => [APIError::THROTTLED, 429];
        yield 'INVALID_SHARED_SECRET' => [APIError::INVALID_SHARED_SECRET, 496];
        yield 'INVALID_USER_ID' => [APIError::INVALID_USER_ID, 497];
        yield 'INTERNAL_ERROR' => [APIError::INTERNAL_ERROR, 500];
    }

    public function testApiErrorClassification(): void
    {
        self::assertTrue(APIError::RECEIPT_NO_LONGER_VALID->isCanceledReceipt());
        self::assertTrue(APIError::THROTTLED->isRetryable());
        self::assertTrue(APIError::INTERNAL_ERROR->isRetryable());

        foreach ([APIError::INVALID_RECEIPT, APIError::INVALID_SHARED_SECRET, APIError::INVALID_USER_ID] as $case) {
            self::assertFalse($case->isCanceledReceipt(), $case->name);
            self::assertFalse($case->isRetryable(), $case->name);
        }
        self::assertFalse(APIError::THROTTLED->isCanceledReceipt());
        self::assertFalse(APIError::RECEIPT_NO_LONGER_VALID->isRetryable());
    }

    public function testApiErrorFromException(): void
    {
        self::assertSame(APIError::THROTTLED, APIError::fromException(new ValidationException('x', 429)));
        self::assertNull(APIError::fromException(new ValidationException('connection failed')));
        self::assertNull(APIError::fromException(new ValidationException('teapot', 418)));
    }

    public function testProductType(): void
    {
        self::assertTrue(ProductType::CONSUMABLE->isConsumable());
        self::assertFalse(ProductType::CONSUMABLE->isEntitlement());
        self::assertFalse(ProductType::CONSUMABLE->isSubscription());

        self::assertTrue(ProductType::ENTITLED->isEntitlement());
        self::assertFalse(ProductType::ENTITLED->isConsumable());

        self::assertTrue(ProductType::SUBSCRIPTION->isSubscription());
        self::assertFalse(ProductType::SUBSCRIPTION->isEntitlement());

        self::assertSame(ProductType::ENTITLED, ProductType::from('ENTITLED'));
    }

    public function testCancelReasonAndFulfillmentResultValues(): void
    {
        self::assertSame(CancelReason::UNAVAILABLE, CancelReason::from(0));
        self::assertSame(CancelReason::CUSTOMER_CANCELED, CancelReason::from(1));
        self::assertSame(CancelReason::SYSTEM_CANCELED, CancelReason::from(2));

        self::assertSame(
            ['FULFILLED', 'EXISTING_PURCHASE', 'NOT_ELIGIBLE', 'UNAVAILABLE'],
            array_map(static fn (FulfillmentResult $r) => $r->value, FulfillmentResult::cases())
        );
    }

    public function testPromotionEnums(): void
    {
        self::assertSame(PromotionType::INTRODUCTORY_PRICE, PromotionType::from('Introductory Price - All Customers'));
        self::assertSame(
            PromotionType::PROMOTIONAL_PRICE_LAPSED_CUSTOMERS,
            PromotionType::from('Promotional Price - Lapsed Customers')
        );
        self::assertSame(PromotionType::RETENTION_OFFER, PromotionType::from('Retention Offer'));

        self::assertTrue(PromotionStatus::IN_PROGRESS->isActive());
        self::assertFalse(PromotionStatus::QUEUED->isActive());
        self::assertFalse(PromotionStatus::COMPLETED->isActive());
    }
}
