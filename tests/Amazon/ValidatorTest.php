<?php
use ReceiptValidator\Amazon\Validator as AmazonValidator;
use ReceiptValidator\Amazon\Response;

/**
 * @group library
 */
class AmazonValidatorTest extends PHPUnit_Framework_TestCase
{

  /**
   * @var AmazonValidator
   */
  private $validator;

  public function setUp()
  {
    parent::setUp();

    $this->validator = new AmazonValidator();
  }

  public function testSetEndpoint()
  {
    $this->validator->setDeveloperSecret('SECRET');

    $this->assertEquals('SECRET', $this->validator->getDeveloperSecret());
  }

  public function testValidateWithNoReceiptData()
  {
    $response = $this->validator->setDeveloperSecret("NA")->setReceiptId("ID")->setUserId("ID")->validate();

    $this->assertFalse($response->isValid(), 'receipt must be invalid');
  }
}
