<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests\AppleAppStore;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\AppleAppStore\Response;
use ReceiptValidator\Environment;

/**
 * @group apple-app-store
 */
#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    #[DataProvider('responseProvider')]
    public function testResponseParsing(array $raw, array $expected): void
    {
        $r = new Response($raw);

        self::assertSame($expected['revision'],   $r->getRevision());
        self::assertSame($expected['bundleId'],   $r->getBundleId());
        self::assertSame($expected['appAppleId'], $r->getAppAppleId());
        self::assertSame($expected['hasMore'],    $r->hasMore());
        self::assertSame($expected['environment'],$r->getEnvironment());
        self::assertCount($expected['txCount'],   $r->getTransactions());
    }

    public static function responseProvider(): array
    {
        return [
            'valid' => [
                'raw' => [
                    'revision'   => 'rev-1',
                    'bundleId'   => 'com.example.app',
                    'appAppleId' => 123456789,
                    'environment'=> 'Production',
                    'hasMore'    => true,
                    // no signedTransactions provided here
                ],
                'expected' => [
                    'revision'   => 'rev-1',
                    'bundleId'   => 'com.example.app',
                    'appAppleId' => 123456789,
                    'hasMore'    => true,
                    'environment'=> Environment::PRODUCTION,
                    'txCount'    => 0,
                ],
            ],
            'empty' => [
                'raw' => [],
                'expected' => [
                    'revision'   => null,
                    'bundleId'   => null,
                    'appAppleId' => null,
                    'hasMore'    => false,
                    // default environment comes from toEnvironment() -> PRODUCTION (since key missing)
                    'environment'=> Environment::PRODUCTION,
                    'txCount'    => 0,
                ],
            ],
        ];
    }
}
