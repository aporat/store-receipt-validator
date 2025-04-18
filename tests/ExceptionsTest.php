<?php

namespace ReceiptValidator\Tests;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Exceptions\ValidationException;

class ExceptionsTest extends TestCase
{
    public function testRunTimeException(): void
    {
        $e = new ValidationException();

        $this->assertInstanceOf(ValidationException::class, $e);
    }
}
