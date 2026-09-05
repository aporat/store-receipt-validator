<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay\JWT;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;
use ReceiptValidator\GooglePlay\JWT\CallbackAccessTokenProvider;
use RuntimeException;

#[CoversClass(CallbackAccessTokenProvider::class)]
final class CallbackAccessTokenProviderTest extends TestCase
{
    public function testReturnsTokenFromCallback(): void
    {
        $provider = new CallbackAccessTokenProvider(static fn (): string => 'ya29.token');

        self::assertSame('ya29.token', $provider->getAccessToken());
    }

    public function testWrapsCallbackExceptions(): void
    {
        $provider = new CallbackAccessTokenProvider(static function (): string {
            throw new RuntimeException('boom');
        });

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Access token callback failed: boom');

        $provider->getAccessToken();
    }

    public function testRejectsEmptyToken(): void
    {
        $provider = new CallbackAccessTokenProvider(static fn (): string => '');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('non-empty string');

        $provider->getAccessToken();
    }

    public function testRejectsNonStringToken(): void
    {
        $provider = new CallbackAccessTokenProvider(static fn (): ?string => null);

        $this->expectException(ValidationException::class);

        $provider->getAccessToken();
    }
}
