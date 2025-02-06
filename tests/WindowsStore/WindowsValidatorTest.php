<?php

namespace ReceiptValidator\Tests\WindowsStore;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\RunTimeException;
use ReceiptValidator\WindowsStore\Validator;

class WindowsValidatorTest extends TestCase
{

    public function testValidateFails(): void
    {
        $this->expectException(RunTimeException::class);
        $this->expectExceptionMessage('Invalid XML');

        $validator = new Validator();
        $validator->validate('foo bar');
    }

}
