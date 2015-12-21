<?php

/**
 * @group library
 */
class ExceptionsTest extends \PHPUnit_Framework_TestCase
{

  public function testRunTimeException()
  {
    $e = new \ReceiptValidator\RunTimeException();

    $this->assertInstanceOf("\ReceiptValidator\RunTimeException", $e);
  }
}
