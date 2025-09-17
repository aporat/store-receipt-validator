<?php

declare(strict_types=1);

namespace ReceiptValidator\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;

#[CoversClass(Environment::class)]
final class EnvironmentTest extends TestCase
{
    #[Test]
    public function from_string_returns_correct_enum_case(): void
    {
        $cases = [
            'sandbox'    => Environment::SANDBOX,
            'SANDBOX'    => Environment::SANDBOX,
            'production' => Environment::PRODUCTION,
            'PRODUCTION' => Environment::PRODUCTION,
        ];

        foreach ($cases as $value => $expected) {
            self::assertSame($expected, Environment::fromString($value));
        }
    }

    #[Test]
    public function from_string_throws_exception_on_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid environment: dev');

        Environment::fromString('dev');
    }

    #[Test]
    public function enum_values_are_correct(): void
    {
        self::assertSame('sandbox', Environment::SANDBOX->value);
        self::assertSame('production', Environment::PRODUCTION->value);
    }
}
