<?php

use PHPUnit\Framework\TestCase;

/**
 * @group library
 */
class ExceptionsTest extends TestCase
{

  public function testRunTimeException()
  {
    $e = new \ReceiptValidator\RunTimeException();

    $this->assertInstanceOf("\ReceiptValidator\RunTimeException", $e);
  }
}
