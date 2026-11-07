<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\GooglePlay;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\GooglePlay\DeferSubscriptionResponse;

#[CoversClass(DeferSubscriptionResponse::class)]
final class DeferSubscriptionResponseTest extends TestCase
{
    public function testSkipsEntriesThatAreNotUsable(): void
    {
        $response = new DeferSubscriptionResponse([
            'itemExpiryTimeDetails' => [
                'not-an-item',
                ['productId' => 'app.example.subscription', 'expiryTime' => '2026-11-05T14:32:11Z'],
                ['productId' => 'app.example.no-expiry'],
                ['expiryTime' => '2026-11-06T00:00:00Z'],
            ],
        ]);

        self::assertSame(['app.example.subscription'], array_keys($response->getItemExpiryTimes()));
        self::assertSame('2026-11-05T14:32:11+00:00', $response->getExpiryTime('app.example.subscription')?->toIso8601String());
        self::assertSame('2026-11-05T14:32:11+00:00', $response->getExpiryTime()?->toIso8601String());
        self::assertNull($response->getExpiryTime('app.example.no-expiry'));
    }

    public function testEmptyPayloadHasNoExpiryTimes(): void
    {
        $response = new DeferSubscriptionResponse([]);

        self::assertSame([], $response->getItemExpiryTimes());
        self::assertNull($response->getExpiryTime());
        self::assertSame([], $response->getRawData());

        self::assertSame([], (new DeferSubscriptionResponse(['itemExpiryTimeDetails' => 'nope']))->getItemExpiryTimes());
    }
}
