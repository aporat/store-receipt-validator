<?php

namespace ReceiptValidator\Tests;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Environment;
use InvalidArgumentException;

class EnvironmentTest extends TestCase
{
    public function test_from_string_returns_sandbox()
    {
        $this->assertSame(Environment::SANDBOX, Environment::fromString('sandbox'));
        $this->assertSame(Environment::SANDBOX, Environment::fromString('SANDBOX'));
    }

    public function test_from_string_returns_production()
    {
        $this->assertSame(Environment::PRODUCTION, Environment::fromString('production'));
        $this->assertSame(Environment::PRODUCTION, Environment::fromString('PRODUCTION'));
    }

    public function test_from_string_throws_exception_on_invalid_value()
    {
        $this->expectException(InvalidArgumentException::class);
        Environment::fromString('dev');
    }
}
