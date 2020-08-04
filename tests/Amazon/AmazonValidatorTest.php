<?php

namespace ReceiptValidator\Tests\Amazon;

use PHPUnit\Framework\TestCase;
use ReceiptValidator\Amazon\Validator as AmazonValidator;

/**
 * @group library
 */
class AmazonValidatorTest extends TestCase
{
    /**
     * @var AmazonValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AmazonValidator();
    }

    public function testSetEndpoint(): void
    {
        $this->validator->setDeveloperSecret('SECRET');

        $this->assertEquals('SECRET', $this->validator->getDeveloperSecret());

        $this->validator->setEndpoint(AmazonValidator::ENDPOINT_PRODUCTION);

        $this->assertEquals(AmazonValidator::ENDPOINT_PRODUCTION, $this->validator->getEndpoint());
    }

    public function testValidateWithNoReceiptData(): void
    {
        $response = $this->validator->setDeveloperSecret('NA')->setReceiptId('ID')->setUserId('ID')->validate();

        $this->assertFalse($response->isValid(), 'receipt must be invalid');
    }
}
