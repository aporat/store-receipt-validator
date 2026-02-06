<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\iTunes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\iTunes\APIError;

/**
 * @group itunes
 */
#[CoversClass(APIError::class)]
final class APIErrorTest extends TestCase
{
    #[DataProvider('backingValueProvider')]
    public function testEnumBackingValue(APIError $case, int $expectedCode): void
    {
        self::assertSame($expectedCode, $case->value);
    }

    public static function backingValueProvider(): iterable
    {
        yield 'VALID' => [APIError::VALID, 0];
        yield 'JSON_INVALID' => [APIError::JSON_INVALID, 21000];
        yield 'RECEIPT_DATA_MALFORMED' => [APIError::RECEIPT_DATA_MALFORMED, 21002];
        yield 'RECEIPT_AUTHENTICATION_FAILED' => [APIError::RECEIPT_AUTHENTICATION_FAILED, 21003];
        yield 'SHARED_SECRET_INVALID' => [APIError::SHARED_SECRET_INVALID, 21004];
        yield 'SERVER_UNAVAILABLE' => [APIError::SERVER_UNAVAILABLE, 21005];
        yield 'SUBSCRIPTION_EXPIRED' => [APIError::SUBSCRIPTION_EXPIRED, 21006];
        yield 'SANDBOX_RECEIPT_ON_PRODUCTION' => [APIError::SANDBOX_RECEIPT_ON_PRODUCTION, 21007];
        yield 'PRODUCTION_RECEIPT_ON_SANDBOX' => [APIError::PRODUCTION_RECEIPT_ON_SANDBOX, 21008];
        yield 'INTERNAL_DATA_ACCESS_ERROR' => [APIError::INTERNAL_DATA_ACCESS_ERROR, 21009];
        yield 'USER_ACCOUNT_NOT_FOUND' => [APIError::USER_ACCOUNT_NOT_FOUND, 21010];
        yield 'INTERNAL_ERROR' => [APIError::INTERNAL_ERROR, 21100];
    }

    public function testAllCasesHaveMessages(): void
    {
        foreach (APIError::cases() as $case) {
            $message = $case->message();
            self::assertNotEmpty($message, sprintf('%s should have a non-empty message', $case->name));
            self::assertIsString($message);
        }
    }

    #[DataProvider('messageProvider')]
    public function testMessageContent(APIError $case, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, $case->message());
    }

    public static function messageProvider(): iterable
    {
        yield 'VALID' => [APIError::VALID, 'The receipt is valid.'];
        yield 'JSON_INVALID' => [APIError::JSON_INVALID, 'The App Store could not read the JSON object you provided.'];
        yield 'RECEIPT_DATA_MALFORMED' => [APIError::RECEIPT_DATA_MALFORMED, 'The data in the receipt-data property was malformed.'];
        yield 'RECEIPT_AUTHENTICATION_FAILED' => [APIError::RECEIPT_AUTHENTICATION_FAILED, 'The receipt could not be authenticated.'];
        yield 'SHARED_SECRET_INVALID' => [APIError::SHARED_SECRET_INVALID, 'The shared secret you provided does not match the shared secret on file for your account.'];
        yield 'SERVER_UNAVAILABLE' => [APIError::SERVER_UNAVAILABLE, 'The receipt server is not currently available.'];
        yield 'SUBSCRIPTION_EXPIRED' => [APIError::SUBSCRIPTION_EXPIRED, 'This receipt is valid but the subscription has expired.'];
        yield 'SANDBOX_RECEIPT_ON_PRODUCTION' => [APIError::SANDBOX_RECEIPT_ON_PRODUCTION, 'This receipt is from the test environment, but it was sent to the production environment.'];
        yield 'PRODUCTION_RECEIPT_ON_SANDBOX' => [APIError::PRODUCTION_RECEIPT_ON_SANDBOX, 'This receipt is from the production environment, but it was sent to the test environment.'];
        yield 'INTERNAL_DATA_ACCESS_ERROR' => [APIError::INTERNAL_DATA_ACCESS_ERROR, 'Internal data access error.'];
        yield 'USER_ACCOUNT_NOT_FOUND' => [APIError::USER_ACCOUNT_NOT_FOUND, 'The user account cannot be found or has been deleted.'];
        yield 'INTERNAL_ERROR' => [APIError::INTERNAL_ERROR, 'An internal server error occurred.'];
    }

    #[DataProvider('fromIntProvider')]
    public function testFromInt(int $code, ?APIError $expected): void
    {
        self::assertSame($expected, APIError::fromInt($code));
    }

    public static function fromIntProvider(): iterable
    {
        yield 'VALID' => [0, APIError::VALID];
        yield 'JSON_INVALID' => [21000, APIError::JSON_INVALID];
        yield 'SHARED_SECRET_INVALID' => [21004, APIError::SHARED_SECRET_INVALID];
        yield 'INTERNAL_ERROR' => [21100, APIError::INTERNAL_ERROR];
        yield 'unknown code returns null' => [99999, null];
        yield 'negative returns null' => [-1, null];
        yield 'gap code 21001 returns null' => [21001, null];
    }

    public function testAllCasesHaveUniqueMessages(): void
    {
        $messages = [];
        foreach (APIError::cases() as $case) {
            $messages[$case->name] = $case->message();
        }

        $uniqueMessages = array_unique($messages);
        self::assertCount(
            count($uniqueMessages),
            $messages,
            'Some iTunes API errors share the same message text',
        );
    }

    public function testCaseCount(): void
    {
        self::assertCount(12, APIError::cases());
    }
}
