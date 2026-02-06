<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\APIError;

/**
 * @group apple-app-store
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
        yield 'GENERAL_BAD_REQUEST' => [APIError::GENERAL_BAD_REQUEST, 4000000];
        yield 'INVALID_APP_IDENTIFIER' => [APIError::INVALID_APP_IDENTIFIER, 4000002];
        yield 'INVALID_TRANSACTION_ID' => [APIError::INVALID_TRANSACTION_ID, 4000006];
        yield 'ACCOUNT_NOT_FOUND' => [APIError::ACCOUNT_NOT_FOUND, 4040001];
        yield 'ACCOUNT_NOT_FOUND_RETRYABLE' => [APIError::ACCOUNT_NOT_FOUND_RETRYABLE, 4040002];
        yield 'RATE_LIMIT_EXCEEDED' => [APIError::RATE_LIMIT_EXCEEDED, 4290000];
        yield 'GENERAL_INTERNAL' => [APIError::GENERAL_INTERNAL, 5000000];
        yield 'GENERAL_INTERNAL_RETRYABLE' => [APIError::GENERAL_INTERNAL_RETRYABLE, 5000001];
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
        yield 'GENERAL_BAD_REQUEST' => [APIError::GENERAL_BAD_REQUEST, 'The request was invalid.'];
        yield 'INVALID_APP_IDENTIFIER' => [APIError::INVALID_APP_IDENTIFIER, 'The app identifier is invalid.'];
        yield 'INVALID_TRANSACTION_ID' => [APIError::INVALID_TRANSACTION_ID, 'The transaction identifier is invalid.'];
        yield 'ACCOUNT_NOT_FOUND' => [APIError::ACCOUNT_NOT_FOUND, "The App Store account wasn\u{2019}t found."];
        yield 'ACCOUNT_NOT_FOUND_RETRYABLE' => [APIError::ACCOUNT_NOT_FOUND_RETRYABLE, "The App Store account wasn\u{2019}t found (retryable)."];
        yield 'RATE_LIMIT_EXCEEDED' => [APIError::RATE_LIMIT_EXCEEDED, 'The request exceeded the rate limit.'];
        yield 'GENERAL_INTERNAL' => [APIError::GENERAL_INTERNAL, 'A general internal error occurred.'];
        yield 'GENERAL_INTERNAL_RETRYABLE' => [APIError::GENERAL_INTERNAL_RETRYABLE, 'A general internal error occurred (retryable).'];
        yield 'SUBSCRIPTION_EXTENSION_INELIGIBLE' => [APIError::SUBSCRIPTION_EXTENSION_INELIGIBLE, "The subscription isn\u{2019}t eligible for an extension."];
        yield 'TRANSACTION_ID_NOT_FOUND' => [APIError::TRANSACTION_ID_NOT_FOUND, "The transaction identifier wasn\u{2019}t found."];
    }

    #[DataProvider('retryableProvider')]
    public function testIsRetryable(APIError $case, bool $expected): void
    {
        self::assertSame($expected, $case->isRetryable());
    }

    public static function retryableProvider(): iterable
    {
        yield 'ACCOUNT_NOT_FOUND_RETRYABLE' => [APIError::ACCOUNT_NOT_FOUND_RETRYABLE, true];
        yield 'APP_NOT_FOUND_RETRYABLE' => [APIError::APP_NOT_FOUND_RETRYABLE, true];
        yield 'ORIGINAL_TRANSACTION_ID_NOT_FOUND_RETRYABLE' => [APIError::ORIGINAL_TRANSACTION_ID_NOT_FOUND_RETRYABLE, true];
        yield 'GENERAL_INTERNAL_RETRYABLE' => [APIError::GENERAL_INTERNAL_RETRYABLE, true];
        yield 'GENERAL_BAD_REQUEST is not retryable' => [APIError::GENERAL_BAD_REQUEST, false];
        yield 'ACCOUNT_NOT_FOUND is not retryable' => [APIError::ACCOUNT_NOT_FOUND, false];
        yield 'APP_NOT_FOUND is not retryable' => [APIError::APP_NOT_FOUND, false];
        yield 'GENERAL_INTERNAL is not retryable' => [APIError::GENERAL_INTERNAL, false];
        yield 'RATE_LIMIT_EXCEEDED is not retryable' => [APIError::RATE_LIMIT_EXCEEDED, false];
    }

    #[DataProvider('fromIntProvider')]
    public function testFromInt(int $code, ?APIError $expected): void
    {
        self::assertSame($expected, APIError::fromInt($code));
    }

    public static function fromIntProvider(): iterable
    {
        yield 'valid code' => [4000000, APIError::GENERAL_BAD_REQUEST];
        yield 'another valid code' => [4290000, APIError::RATE_LIMIT_EXCEEDED];
        yield 'retryable code' => [5000001, APIError::GENERAL_INTERNAL_RETRYABLE];
        yield 'unknown code returns null' => [9999999, null];
        yield 'zero returns null' => [0, null];
        yield 'negative returns null' => [-1, null];
    }

    public function testRetryableErrorsHaveNonRetryableCounterpart(): void
    {
        $retryable = array_filter(
            APIError::cases(),
            static fn (APIError $e) => $e->isRetryable(),
        );

        self::assertNotEmpty($retryable);

        foreach ($retryable as $case) {
            self::assertStringContainsString(
                'RETRYABLE',
                $case->name,
                sprintf('%s is retryable but name does not contain RETRYABLE', $case->name),
            );
        }
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
            'Some API errors share the same message text',
        );
    }

    public function testErrorCodeRanges(): void
    {
        foreach (APIError::cases() as $case) {
            self::assertGreaterThan(
                0,
                $case->value,
                sprintf('%s should have a positive error code', $case->name),
            );

            $prefix = intdiv($case->value, 1000000);
            self::assertContains(
                $prefix,
                [4, 5],
                sprintf('%s error code %d should start with 4 (client) or 5 (server)', $case->name, $case->value),
            );
        }
    }
}
