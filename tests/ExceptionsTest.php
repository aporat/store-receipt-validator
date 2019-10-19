<?php

namespace ReceiptValidator\Tests;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\RunTimeException;

/**
 * @group library
 */
class ExceptionsTest extends TestCase
{
    public function testRunTimeException(): void
    {
        $e = new RunTimeException();

        $this->assertInstanceOf(RunTimeException::class, $e);
    }
}
