<?php

namespace ReceiptValidator\Tests;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use InvalidArgumentException;

/**
 * @coversDefaultClass \ReceiptValidator\Environment
 */
class EnvironmentTest extends TestCase
{
    /**
     * @covers ::fromString
     */
    public function test_from_string_returns_sandbox(): void
    {
        $this->assertSame(Environment::SANDBOX, Environment::fromString('sandbox'));
        $this->assertSame(Environment::SANDBOX, Environment::fromString('SANDBOX'));
    }

    /**
     * @covers ::fromString
     */
    public function test_from_string_returns_production(): void
    {
        $this->assertSame(Environment::PRODUCTION, Environment::fromString('production'));
        $this->assertSame(Environment::PRODUCTION, Environment::fromString('PRODUCTION'));
    }

    /**
     * @covers ::fromString
     */
    public function test_from_string_throws_exception_on_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid environment: dev');
        Environment::fromString('dev');
    }

    /**
     * @coversNothing
     */
    public function testEnumValuesAreCorrect(): void
    {
        $this->assertSame('sandbox', Environment::SANDBOX->value);
        $this->assertSame('production', Environment::PRODUCTION->value);
    }
}
